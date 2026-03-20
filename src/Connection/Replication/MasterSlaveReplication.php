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
use Predis\Client_Exception;
use Predis\Command\Command;
use Predis\Command\Command_Interface;
use Predis\Command\Raw_Command;
use Predis\Connection\Abstract_Aggregate_Connection;
use Predis\Connection\Connection_Exception;
use Predis\Connection\Factory_Interface;
use Predis\Connection\Node_Connection_Interface;
use Predis\Connection\Parameters_Interface;
use Predis\Connection\Relay_Factory;
use Predis\Replication\Missing_Master_Exception;
use Predis\Replication\Replication_Strategy;
use Predis\Response\Error_Interface as ResponseErrorInterface;
use Predis\Timeout_Exception;
use Throwable;
/**
 * Aggregate connection handling replication of Redis nodes configured in a
 * single master / multiple slaves setup.
 */
class Master_Slave_Replication extends Abstract_Aggregate_Connection implements Replication_Interface
{
    /**
     * @var ReplicationStrategy
     */
    protected $strategy;
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
     * @var NodeConnectionInterface[]
     */
    protected $aliases = [];
    /**
     * @var NodeConnectionInterface
     */
    protected $current;
    /**
     * @var bool
     */
    protected $auto_discovery = false;
    /**
     * @var FactoryInterface
     */
    protected $connection_factory;
    /**
     * {@inheritdoc}
     */
    public function __construct(?Replication_Strategy $strategy = null)
    {
        $this->strategy = $strategy ?: new Replication_Strategy();
    }
    /**
     * Configures the automatic discovery of the replication configuration on failure.
     *
     * @param bool $value Enable or disable auto discovery.
     */
    public function set_auto_discovery($value): void
    {
        if (!$this->connection_factory) {
            throw new Client_Exception('Automatic discovery requires a connection factory');
        }
        $this->auto_discovery = (bool) $value;
    }
    /**
     * Sets the connection factory used to create the connections by the auto
     * discovery procedure.
     *
     * @param FactoryInterface $connectionFactory Connection factory instance.
     */
    public function set_connection_factory(Factory_Interface $connection_factory): void
    {
        $this->connection_factory = $connection_factory;
    }
    /**
     * Resets the connection state.
     */
    protected function reset()
    {
        $this->current = null;
    }
    /**
     * {@inheritdoc}
     */
    public function add(Node_Connection_Interface $connection): void
    {
        $parameters = $connection->get_parameters();
        if ('master' === $parameters->role) {
            $this->master = $connection;
        } else {
            // everything else is considered a slvave.
            $this->slaves[] = $connection;
        }
        if (isset($parameters->alias)) {
            $this->aliases[$parameters->alias] = $connection;
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
        } else {
            return false;
        }
        unset($this->pool[(string) $connection]);
        if ($this->aliases && $alias = $connection->get_parameters()->alias) {
            unset($this->aliases[$alias]);
        }
        $this->reset();
        return true;
    }
    /**
     * {@inheritdoc}
     */
    public function get_connection_by_command(Command_Interface $command)
    {
        if (!$this->current) {
            if ($this->strategy->is_read_operation($command) && $slave = $this->pick_slave()) {
                $this->current = $slave;
            } else {
                $this->current = $this->get_master_or_die();
            }
            return $this->current;
        }
        if ($this->current === $master = $this->get_master_or_die()) {
            return $master;
        }
        if (!$this->strategy->is_read_operation($command) || !$this->slaves) {
            $this->current = $master;
        }
        return $this->current;
    }
    /**
     * {@inheritdoc}
     */
    public function get_connection_by_id($id)
    {
        return $this->pool[$id] ?? null;
    }
    /**
     * Returns a connection instance by its alias.
     *
     * @param string $alias Connection alias.
     *
     * @return NodeConnectionInterface|null
     */
    public function get_connection_by_alias($alias)
    {
        return $this->aliases[$alias] ?? null;
    }
    /**
     * Returns a connection by its role.
     *
     * @param string $role Connection role (`master` or `slave`)
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
        return null;
    }
    /**
     * Switches the internal connection in use by the backend.
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
        $this->current = $connection;
    }
    /**
     * {@inheritdoc}
     */
    public function switch_to_master(): void
    {
        if (!$connection = $this->get_connection_by_role('master')) {
            throw new InvalidArgumentException('Invalid connection or connection not found.');
        }
        $this->switch_to($connection);
    }
    /**
     * {@inheritdoc}
     */
    public function switch_to_slave(): void
    {
        if (!$connection = $this->get_connection_by_role('slave')) {
            throw new InvalidArgumentException('Invalid connection or connection not found.');
        }
        $this->switch_to($connection);
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
        return $this->master;
    }
    /**
     * Returns the connection associated to the master server.
     *
     * @return NodeConnectionInterface
     */
    private function get_master_or_die()
    {
        if (!$connection = $this->get_master()) {
            throw new Missing_Master_Exception('No master server available for replication');
        }
        return $connection;
    }
    /**
     * {@inheritdoc}
     */
    public function get_slaves()
    {
        return $this->slaves;
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
     * Returns a random slave.
     *
     * @return NodeConnectionInterface|null
     */
    protected function pick_slave()
    {
        if (!$this->slaves) {
            return null;
        }
        return $this->slaves[array_rand($this->slaves)];
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
                if (!$this->current = $this->get_master()) {
                    throw new Client_Exception('No available connection for replication');
                }
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
     * Handles response from INFO.
     *
     * @param string $response
     */
    private function handle_info_response($response): array
    {
        $info = [];
        foreach (preg_split('/\r?\n/', $response) as $row) {
            if (strpos($row, ':') === false) {
                continue;
            }
            [$k, $v] = explode(':', $row, 2);
            $info[$k] = $v;
        }
        return $info;
    }
    /**
     * Fetches the replication configuration from one of the servers.
     */
    public function discover(): void
    {
        if (!$this->connection_factory) {
            throw new Client_Exception('Discovery requires a connection factory');
        }
        while (true) {
            try {
                if ($connection = $this->get_master()) {
                    $this->discover_from_master($connection, $this->connection_factory);
                    break;
                } elseif ($connection = $this->pick_slave()) {
                    $this->discover_from_slave($connection, $this->connection_factory);
                    break;
                }
                throw new Client_Exception('No connection available for discovery');
            } catch (Connection_Exception $exception) {
                $this->remove($connection);
            }
        }
    }
    /**
     * Discovers the replication configuration by contacting the master node.
     *
     * @param NodeConnectionInterface $connection        Connection to the master node.
     * @param FactoryInterface        $connectionFactory Connection factory instance.
     */
    protected function discover_from_master(Node_Connection_Interface $connection, Factory_Interface $connection_factory)
    {
        $response = $connection->execute_command(Raw_Command::create('INFO', 'REPLICATION'));
        $replication = $this->handle_info_response($response);
        if ($replication['role'] !== 'master') {
            throw new Client_Exception("Role mismatch (expected master, got slave) [{$connection}]");
        }
        $this->slaves = [];
        foreach ($replication as $k => $v) {
            $parameters = null;
            if (strpos($k, 'slave') === 0 && preg_match('/ip=(?P<host>.*),port=(?P<port>\d+)/', $v, $parameters)) {
                $slave_connection = $connection_factory->create(['host' => $parameters['host'], 'port' => $parameters['port'], 'role' => 'slave']);
                $this->add($slave_connection);
            }
        }
    }
    /**
     * Discovers the replication configuration by contacting one of the slaves.
     *
     * @param NodeConnectionInterface $connection        Connection to one of the slaves.
     * @param FactoryInterface        $connectionFactory Connection factory instance.
     */
    protected function discover_from_slave(Node_Connection_Interface $connection, Factory_Interface $connection_factory)
    {
        $response = $connection->execute_command(Raw_Command::create('INFO', 'REPLICATION'));
        $replication = $this->handle_info_response($response);
        if ($replication['role'] !== 'slave') {
            throw new Client_Exception("Role mismatch (expected slave, got master) [{$connection}]");
        }
        $master_connection = $connection_factory->create(['host' => $replication['master_host'], 'port' => $replication['master_port'], 'role' => 'master']);
        $this->add($master_connection);
        $this->discover_from_master($master_connection, $connection_factory);
    }
    /**
     * Retries the execution of a command upon slave failure.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $method  Actual method.
     *
     * @return mixed
     * @throws Throwable
     */
    private function retry_command_on_failure(Command_Interface $command, string $method)
    {
        $parameters = $this->get_parameters();
        if (!$parameters->is_disabled_retry() && !$this->connection_factory instanceof Relay_Factory) {
            $retry = $parameters->retry;
            $retry->update_catchable_exceptions([Missing_Master_Exception::class]);
            return $retry->call_with_retry(function () use ($command, $method) {
                return $this->execute_command_internal($command, $method);
            }, function (Throwable $exception): void {
                $this->on_fail_callback($exception);
            });
        }
        while (true) {
            try {
                $connection = $this->get_connection_by_command($command);
                $response = $connection->{$method}($command);
                if ($response instanceof Response_Error_Interface && $response->get_error_type() === 'LOADING') {
                    throw new Connection_Exception($connection, "Redis is loading the dataset in memory [{$connection}]");
                }
                break;
            } catch (Connection_Exception $exception) {
                $this->on_connection_exception_callback($exception);
            } catch (Missing_Master_Exception $exception) {
                $this->on_missing_master_exception($exception);
            }
        }
        return $response;
    }
    /**
     * Executes command against valid connection.
     *
     * @return mixed
     * @throws ConnectionException
     */
    protected function execute_command_internal(Command_Interface $command, string $method)
    {
        $connection = $this->get_connection_by_command($command);
        $response = $connection->{$method}($command);
        if ($response instanceof Response_Error_Interface && $response->get_error_type() === 'LOADING') {
            throw new Connection_Exception($connection, "Redis is loading the dataset in memory [{$connection}]");
        }
        return $response;
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
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return ['master', 'slaves', 'pool', 'aliases', 'strategy'];
    }
    /**
     * {@inheritdoc}
     */
    public function get_parameters(): ?Parameters_Interface
    {
        if (isset($this->master)) {
            return $this->master->get_parameters();
        }
        $slave = $this->pick_slave();
        if (null !== $slave) {
            return $slave->get_parameters();
        }
        return null;
    }
    /**
     * Handle connection exception.
     *
     * @throws ClientException|ConnectionException
     */
    private function on_connection_exception_callback(Connection_Exception $exception): void
    {
        $connection = $exception->get_connection();
        $connection->disconnect();
        if ($connection === $this->master && !$this->auto_discovery) {
            // Throw immediately when master connection is failing, even
            // when the command represents a read-only operation, unless
            // automatic discovery has been enabled.
            throw $exception;
        }
        // Otherwise remove the failing slave and attempt to execute
        // the command again on one of the remaining slaves...
        $this->remove($connection);
        // ... that is, unless we have no more connections to use.
        if (!$this->slaves && !$this->master) {
            throw $exception;
        }
        // ... that is, unless we have no more connections to use.
        if ($this->auto_discovery) {
            $this->discover();
        }
    }
    /**
     * Exception handling callback.
     *
     * @throws Throwable
     */
    private function on_fail_callback(Throwable $exception): void
    {
        if ($exception instanceof Connection_Exception) {
            $this->on_connection_exception_callback($exception);
            return;
        }
        if ($exception instanceof Missing_Master_Exception) {
            $this->on_missing_master_exception($exception);
            return;
        }
        if ($exception instanceof Timeout_Exception) {
            $connection = $exception->get_connection();
            if ($connection) {
                $connection->disconnect();
                return;
            }
        }
        throw $exception;
    }
    /**
     * @throws ClientException
     * @throws MissingMasterException
     */
    private function on_missing_master_exception(Missing_Master_Exception $exception): void
    {
        if ($this->auto_discovery) {
            $this->discover();
        } else {
            throw $exception;
        }
    }
}