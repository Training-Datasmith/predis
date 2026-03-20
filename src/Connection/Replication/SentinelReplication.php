<?php

declare (strict_types=1);
/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Predis\Connection\Replication;

use InvalidArgumentException;
use Predis\Command\Command;
use Predis\Command\Command_Interface;
use Predis\Command\Raw_Command;
use Predis\Communication_Exception;
use Predis\Connection\Abstract_Aggregate_Connection;
use Predis\Connection\Connection_Exception;
use Predis\Connection\Factory_Interface as ConnectionFactoryInterface;
use Predis\Connection\Node_Connection_Interface;
use Predis\Connection\Parameters;
use Predis\Connection\Parameters_Interface;
use Predis\Connection\Relay_Factory;
use Predis\Connection\Resource\Exception\Stream_Init_Exception;
use Predis\Replication\Replication_Strategy;
use Predis\Replication\Role_Exception;
use Predis\Response\Error;
use Predis\Response\Error_Interface as ErrorResponseInterface;
use Predis\Response\Server_Exception;
use Predis\Retry\Retry;
use Predis\Retry\Strategy\Exponential_Backoff;
use Throwable;
/**
 * @author Daniele Alessandri <suppakilla@gmail.com>
 * @author Ville Mattila <ville@eventio.fi>
 */
class Sentinel_Replication extends Abstract_Aggregate_Connection implements Replication_Interface
{
    /**
     * @var NodeConnectionInterface
     */
    protected $master;
    /**
     * @var NodeConnectionInterface[]
     */
    protected $slaves = [];
    /**
     * @var NodeConnectionInterface[]
     */
    protected $pool = [];
    /**
     * @var NodeConnectionInterface
     */
    protected $current;
    /**
     * @var string
     */
    protected $service;
    /**
     * @var ConnectionFactoryInterface
     */
    protected $connection_factory;
    /**
     * @var ReplicationStrategy
     */
    protected $strategy;
    /**
     * Sentinel connection parameters.
     *
     * Can contain:
     * - String URIs (e.g., "tcp://127.0.0.1:26379")
     * - Arrays of connection parameters (e.g., ['host' => '127.0.0.1', 'port' => 26379])
     * - ParametersInterface objects
     * - NodeConnectionInterface objects
     *
     * @var array<string|array|ParametersInterface|NodeConnectionInterface>
     */
    protected $sentinels = [];
    /**
     * @var int
     */
    protected $sentinel_index = 0;
    /**
     * @var NodeConnectionInterface
     */
    protected $sentinel_connection;
    /**
     * @var float
     */
    protected $sentinel_timeout = 0.1;
    /**
     * Max number of automatic retries of commands upon server failure.
     *
     * -1 = unlimited retry attempts
     *  0 = no retry attempts (fails immediately)
     *  n = fail only after n retry attempts
     *
     * @var int
     */
    protected $retry_limit = 20;
    /**
     * Time to wait in milliseconds before fetching a new configuration from one
     * of the sentinel servers.
     *
     * @var int
     */
    protected $retry_wait = 1000;
    /**
     * Flag for automatic fetching of available sentinels.
     *
     * @var bool
     */
    protected $update_sentinels = false;
    /**
     * @param string                     $service           Name of the service for autodiscovery.
     * @param array                      $sentinels         Sentinel servers connection parameters.
     * @param ConnectionFactoryInterface $connectionFactory Connection factory instance.
     * @param ReplicationStrategy|null   $strategy          Replication strategy instance.
     */
    public function __construct($service, array $sentinels, Connection_Factory_Interface $connection_factory, ?Replication_Strategy $strategy = null)
    {
        $this->sentinels = $sentinels;
        $this->service = $service;
        $this->connection_factory = $connection_factory;
        $this->strategy = $strategy ?: new Replication_Strategy();
    }
    /**
     * Sets a default timeout for connections to sentinels.
     *
     * When "timeout" is present in the connection parameters of sentinels, its
     * value overrides the default sentinel timeout.
     *
     * @param float $timeout Timeout value.
     */
    public function set_sentinel_timeout($timeout): void
    {
        $this->sentinel_timeout = (float) $timeout;
    }
    /**
     * Sets the maximum number of retries for commands upon server failure.
     *
     * -1 = unlimited retry attempts
     *  0 = no retry attempts (fails immediately)
     *  n = fail only after n retry attempts
     *
     * @param int $retry Number of retry attempts.
     */
    public function set_retry_limit($retry): void
    {
        $this->retry_limit = (int) $retry;
    }
    /**
     * Sets the time to wait (in milliseconds) before fetching a new configuration
     * from one of the sentinels.
     *
     * @param float $milliseconds Time to wait before the next attempt.
     */
    public function set_retry_wait($milliseconds): void
    {
        $this->retry_wait = (float) $milliseconds;
    }
    /**
     * Set automatic fetching of available sentinels.
     *
     * @param bool $update Enable or disable automatic updates.
     */
    public function set_update_sentinels($update): void
    {
        $this->update_sentinels = (bool) $update;
    }
    /**
     * Resets the current connection.
     */
    protected function reset()
    {
        $this->current = null;
    }
    /**
     * Wipes the current list of master and slaves nodes.
     */
    protected function wipe_server_list()
    {
        $this->reset();
        $this->master = null;
        $this->slaves = [];
        $this->pool = [];
    }
    /**
     * {@inheritdoc}
     */
    public function add(Node_Connection_Interface $connection): void
    {
        $parameters = $connection->get_parameters();
        $role = $parameters->role;
        if ('master' === $role) {
            $this->master = $connection;
        } elseif ('sentinel' === $role) {
            $this->sentinels[] = $connection;
            // sentinels are not considered part of the pool.
            return;
        } else {
            // everything else is considered a slave.
            $this->slaves[] = $connection;
        }
        $this->pool[(string) $connection] = $connection;
        $this->reset();
    }
    /**
     * {@inheritdoc}
     */
    public function remove(Node_Connection_Interface $connection): bool
    {
        if ($connection === $this->master) {
            $this->master = null;
        } elseif (false !== $id = array_search($connection, $this->slaves, true)) {
            unset($this->slaves[$id]);
        } elseif (false !== $id = array_search($connection, $this->sentinels, true)) {
            unset($this->sentinels[$id]);
            return true;
        } else {
            return false;
        }
        unset($this->pool[(string) $connection]);
        $this->reset();
        return true;
    }
    /**
     * Creates a new connection to a sentinel server.
     *
     * @return NodeConnectionInterface
     */
    protected function create_sentinel_connection($parameters)
    {
        if ($parameters instanceof Node_Connection_Interface) {
            return $parameters;
        }
        if (is_string($parameters)) {
            $parameters = Parameters::parse($parameters);
        }
        if (is_array($parameters)) {
            // NOTE: sentinels do not accept SELECT command so we must
            // explicitly set it to NULL to avoid problems when using default
            // parameters set via client options.
            $parameters['database'] = null;
            // don't leak password from between configurations
            // https://github.com/predis/predis/pull/807/#discussion_r985764770
            if (!isset($parameters['password'])) {
                $parameters['password'] = null;
            }
            if (!isset($parameters['timeout'])) {
                $parameters['timeout'] = $this->sentinel_timeout;
            }
        }
        return $this->connection_factory->create($parameters);
    }
    /**
     * Returns the current sentinel connection.
     *
     * If there is no active sentinel connection, a new connection is created.
     *
     * @return NodeConnectionInterface
     */
    public function get_sentinel_connection()
    {
        if (!$this->sentinel_connection) {
            if ($this->sentinel_index >= count($this->sentinels)) {
                $this->sentinel_index = 0;
                throw new \Predis\Client_Exception('No sentinel server available for autodiscovery.');
            }
            $sentinel = $this->sentinels[$this->sentinel_index];
            ++$this->sentinel_index;
            $this->sentinel_connection = $this->create_sentinel_connection($sentinel);
        }
        return $this->sentinel_connection;
    }
    /**
     * Fetches an updated list of sentinels from a sentinel.
     */
    public function update_sentinels(): void
    {
        SENTINEL_QUERY:
        $sentinel = $this->get_sentinel_connection();
        try {
            $payload = $sentinel->execute_command(Raw_Command::create('SENTINEL', 'sentinels', $this->service));
            $this->sentinels = [];
            $this->sentinel_index = 0;
            // NOTE: sentinel server does not return itself, so we add it back.
            $this->sentinels[] = $sentinel->get_parameters()->to_array();
            foreach ($payload as $sentinel) {
                $this->sentinels[] = ['host' => $sentinel[3], 'port' => $sentinel[5], 'role' => 'sentinel'];
            }
        } catch (Connection_Exception|Stream_Init_Exception $exception) {
            $this->sentinel_connection = null;
            goto SENTINEL_QUERY;
        }
    }
    /**
     * Fetches the details for the master and slave servers from a sentinel.
     */
    public function query_sentinel(): void
    {
        $this->wipe_server_list();
        $this->update_sentinels();
        $this->get_master();
        $this->get_slaves();
    }
    /**
     * Handles error responses returned by redis-sentinel.
     *
     * @param NodeConnectionInterface $sentinel Connection to a sentinel server.
     * @param ErrorResponseInterface  $error    Error response.
     */
    private function handle_sentinel_error_response(Node_Connection_Interface $sentinel, Error_Response_Interface $error): void
    {
        if ($error->get_error_type() === 'IDONTKNOW') {
            throw new Connection_Exception($sentinel, $error->get_message());
        }
        throw new Server_Exception($error->get_message());
    }
    /**
     * Fetches the details for the master server from a sentinel.
     *
     * @param NodeConnectionInterface $sentinel Connection to a sentinel server.
     * @param string                  $service  Name of the service.
     */
    protected function query_sentinel_for_master(Node_Connection_Interface $sentinel, $service): array
    {
        $payload = $sentinel->execute_command(Raw_Command::create('SENTINEL', 'get-master-addr-by-name', $service));
        if ($payload === null) {
            throw new Server_Exception('ERR No such master with that name');
        }
        if ($payload instanceof Error_Response_Interface) {
            $this->handle_sentinel_error_response($sentinel, $payload);
        }
        return ['host' => $payload[0], 'port' => $payload[1], 'role' => 'master'];
    }
    /**
     * Fetches the details for the slave servers from a sentinel.
     *
     * @param NodeConnectionInterface $sentinel Connection to a sentinel server.
     * @param string                  $service  Name of the service.
     */
    protected function query_sentinel_for_slaves(Node_Connection_Interface $sentinel, $service): array
    {
        $slaves = [];
        $payload = $sentinel->execute_command(Raw_Command::create('SENTINEL', 'slaves', $service));
        if ($payload instanceof Error_Response_Interface) {
            $this->handle_sentinel_error_response($sentinel, $payload);
        }
        foreach ($payload as $slave) {
            $flags = explode(',', $slave[9]);
            if (array_intersect($flags, ['s_down', 'o_down', 'disconnected'])) {
                continue;
            }
            // ensure `master-link-status` is ok
            if (isset($slave[31]) && $slave[31] === 'err') {
                continue;
            }
            $slaves[] = ['host' => $slave[3], 'port' => $slave[5], 'role' => 'slave'];
        }
        return $slaves;
    }
    /**
     * {@inheritdoc}
     */
    public function get_current()
    {
        return $this->current;
    }
    /**
     * {@inheritdoc}
     */
    public function get_master()
    {
        if ($this->master) {
            return $this->master;
        }
        if ($this->update_sentinels) {
            $this->update_sentinels();
        }
        SENTINEL_QUERY:
        $sentinel = $this->get_sentinel_connection();
        try {
            $master_parameters = $this->query_sentinel_for_master($sentinel, $this->service);
            $master_connection = $this->connection_factory->create($master_parameters);
            $this->add($master_connection);
        } catch (Connection_Exception|Stream_Init_Exception $exception) {
            $this->sentinel_connection = null;
            goto SENTINEL_QUERY;
        }
        return $master_connection;
    }
    /**
     * {@inheritdoc}
     */
    public function get_slaves(): array
    {
        if ($this->slaves) {
            return array_values($this->slaves);
        }
        if ($this->update_sentinels) {
            $this->update_sentinels();
        }
        SENTINEL_QUERY:
        $sentinel = $this->get_sentinel_connection();
        try {
            $slaves_parameters = $this->query_sentinel_for_slaves($sentinel, $this->service);
            foreach ($slaves_parameters as $slave_parameters) {
                $this->add($this->connection_factory->create($slave_parameters));
            }
        } catch (Connection_Exception|Stream_Init_Exception $exception) {
            $this->sentinel_connection = null;
            goto SENTINEL_QUERY;
        }
        return array_values($this->slaves);
    }
    /**
     * Returns a random slave.
     *
     * @return NodeConnectionInterface|null
     */
    protected function pick_slave()
    {
        $slaves = $this->get_slaves();
        return $slaves ? $slaves[random_int(1, count($slaves)) - 1] : null;
    }
    /**
     * Returns the connection instance in charge for the given command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return NodeConnectionInterface
     */
    private function get_connection_internal(Command_Interface $command)
    {
        if (!$this->current) {
            if ($this->strategy->is_read_operation($command) && $slave = $this->pick_slave()) {
                $this->current = $slave;
            } else {
                $this->current = $this->get_master();
            }
            return $this->current;
        }
        if ($this->current === $this->master) {
            return $this->current;
        }
        if (!$this->strategy->is_read_operation($command)) {
            $this->current = $this->get_master();
        }
        return $this->current;
    }
    /**
     * Asserts that the specified connection matches an expected role.
     *
     * @param NodeConnectionInterface $connection Connection to a redis server.
     * @param string                  $role       Expected role of the server ("master", "slave" or "sentinel").
     *
     * @throws RoleException|ConnectionException
     */
    protected function assert_connection_role(Node_Connection_Interface $connection, $role)
    {
        $role = strtolower($role);
        $retry = $connection->get_parameters()->retry;
        $actual_role = $retry->call_with_retry(static function () use ($connection) {
            return $connection->execute_command(Raw_Command::create('ROLE'));
        });
        if ($actual_role instanceof Error) {
            throw new Connection_Exception($connection, $actual_role->get_message());
        }
        if ($role !== $actual_role[0]) {
            throw new Role_Exception($connection, "Expected {$role} but got {$actual_role[0]} [{$connection}]");
        }
    }
    /**
     * {@inheritdoc}
     */
    public function get_connection_by_command(Command_Interface $command)
    {
        $connection = $this->get_connection_internal($command);
        if (!$connection->is_connected()) {
            // When we do not have any available slave in the pool we can expect
            // read-only operations to hit the master server.
            $expected_role = $this->strategy->is_read_operation($command) && $this->slaves ? 'slave' : 'master';
            $this->assert_connection_role($connection, $expected_role);
        }
        return $connection;
    }
    /**
     * {@inheritdoc}
     */
    public function get_connection_by_id($id)
    {
        return $this->pool[$id] ?? null;
    }
    /**
     * Returns a connection by its role.
     *
     * @param string $role Connection role (`master`, `slave` or `sentinel`)
     *
     * @return NodeConnectionInterface|null
     */
    public function get_connection_by_role($role)
    {
        if ($role === 'master') {
            return $this->get_master();
        }
        if ($role === 'slave') {
            return $this->pick_slave();
        }
        if ($role === 'sentinel') {
            return $this->get_sentinel_connection();
        }
        return null;
    }
    /**
     * Switches the internal connection in use by the backend.
     *
     * Sentinel connections are not considered as part of the pool, meaning that
     * trying to switch to a sentinel will throw an exception.
     *
     * @param NodeConnectionInterface $connection Connection instance in the pool.
     */
    public function switch_to(Node_Connection_Interface $connection): void
    {
        if ($connection && $connection === $this->current) {
            return;
        }
        if ($connection !== $this->master && !in_array($connection, $this->slaves, true)) {
            throw new InvalidArgumentException('Invalid connection or connection not found.');
        }
        $connection->connect();
        if ($this->current) {
            $this->current->disconnect();
        }
        $this->current = $connection;
    }
    /**
     * {@inheritdoc}
     */
    public function switch_to_master(): void
    {
        $connection = $this->get_connection_by_role('master');
        $this->switch_to($connection);
    }
    /**
     * {@inheritdoc}
     */
    public function switch_to_slave(): void
    {
        $connection = $this->get_connection_by_role('slave');
        $this->switch_to($connection);
    }
    /**
     * {@inheritdoc}
     */
    public function is_connected(): bool
    {
        return $this->current && $this->current->is_connected();
    }
    /**
     * {@inheritdoc}
     */
    public function connect(): void
    {
        if (!$this->current) {
            if (!$this->current = $this->pick_slave()) {
                $this->current = $this->get_master();
            }
        }
        $this->current->connect();
    }
    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        foreach ($this->pool as $connection) {
            $connection->disconnect();
        }
    }
    /**
     * Retries the execution of a command upon server failure after asking a new
     * configuration to one of the sentinels.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $method  Actual method.
     *
     * @return mixed
     */
    private function retry_command_on_failure(Command_Interface $command, string $method)
    {
        $parameters = $this->get_parameters();
        if ($parameters->is_disabled_retry() || $this->connection_factory instanceof Relay_Factory) {
            // Override default parameters, for backward-compatibility
            // with current behaviour
            $retry = new Retry(new Exponential_Backoff($this->retry_wait * 1000, -1), $this->retry_limit);
        } else {
            $retry = $parameters->retry;
        }
        $retry->update_catchable_exceptions([Throwable::class]);
        $do_callback = function () use ($method, $command) {
            $response = $this->get_connection_by_command($command)->{$method}($command);
            if ($response instanceof Error && $response->get_error_type() === 'LOADING') {
                throw new Connection_Exception($this->current, $response->get_message());
            }
            return $response;
        };
        $fail_callback = function (Throwable $exception): void {
            $this->wipe_server_list();
            if ($exception instanceof Communication_Exception) {
                $exception->get_connection()->disconnect();
            }
        };
        return $retry->call_with_retry($do_callback, $fail_callback);
    }
    /**
     * {@inheritdoc}
     */
    public function write_request(Command_Interface $command): void
    {
        $this->retry_command_on_failure($command, __FUNCTION__);
    }
    /**
     * {@inheritdoc}
     */
    public function read_response(Command_Interface $command)
    {
        return $this->retry_command_on_failure($command, __FUNCTION__);
    }
    /**
     * {@inheritdoc}
     */
    public function execute_command(Command_Interface $command)
    {
        return $this->retry_command_on_failure($command, __FUNCTION__);
    }
    /**
     * Returns the underlying replication strategy.
     *
     * @return ReplicationStrategy
     */
    public function get_replication_strategy()
    {
        return $this->strategy;
    }
    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return ['master', 'slaves', 'pool', 'service', 'sentinels', 'connectionFactory', 'strategy'];
    }
    /**
     * {@inheritdoc}
     */
    public function get_parameters(): ?Parameters_Interface
    {
        if (isset($this->master)) {
            return $this->master->get_parameters();
        }
        if (!empty($this->slaves)) {
            return $this->slaves[0]->get_parameters();
        }
        if (!empty($this->sentinels)) {
            $sentinel = $this->sentinels[0];
            // Handle string URIs (e.g., "tcp://127.0.0.1:26379")
            if (is_string($sentinel)) {
                return new Parameters(Parameters::parse($sentinel));
            }
            // After querySentinels(), sentinels array contains plain arrays instead of connection objects
            if (is_array($sentinel)) {
                return new Parameters($sentinel);
            }
            if ($sentinel instanceof Parameters_Interface) {
                return $sentinel;
            }
            return $sentinel->get_parameters();
        }
        return null;
    }
}