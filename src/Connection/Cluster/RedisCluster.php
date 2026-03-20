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
namespace Predis\Connection\Cluster;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use OutOfBoundsException;
use Predis\Client_Exception;
use Predis\Cluster\Redis_Strategy as RedisClusterStrategy;
use Predis\Cluster\Slot_Map;
use Predis\Cluster\Strategy_Interface;
use Predis\Command\Command;
use Predis\Command\Command_Interface;
use Predis\Command\Raw_Command;
use Predis\Connection\Abstract_Aggregate_Connection;
use Predis\Connection\Connection_Exception;
use Predis\Connection\Factory_Interface;
use Predis\Connection\Node_Connection_Interface;
use Predis\Connection\Parameters_Interface;
use Predis\Connection\Relay_Factory;
use Predis\Not_Supported_Exception;
use Predis\Response\Error as ErrorResponse;
use Predis\Response\Error_Interface as ErrorResponseInterface;
use Predis\Response\Server_Exception;
use Predis\Retry\Retry;
use Predis\Retry\Strategy\Exponential_Backoff;
use Predis\Timeout_Exception;
use Return_Type_Will_Change;
use Throwable;
use Traversable;
/**
 * Abstraction for a Redis-backed cluster of nodes (Redis >= 3.0.0).
 *
 * This connection backend offers smart support for redis-cluster by handling
 * automatic slots map (re)generation upon -MOVED or -ASK responses returned by
 * Redis when redirecting a client to a different node.
 *
 * The cluster can be pre-initialized using only a subset of the actual nodes in
 * the cluster, Predis will do the rest by adjusting the slots map and creating
 * the missing underlying connection instances on the fly.
 *
 * It is possible to pre-associate connections to a slots range with the "slots"
 * parameter in the form "$first-$last". This can greatly reduce runtime node
 * guessing and redirections.
 *
 * It is also possible to ask for the full and updated slots map directly to one
 * of the nodes and optionally enable such a behaviour upon -MOVED redirections.
 * Asking for the cluster configuration to Redis is actually done by issuing a
 * CLUSTER SLOTS command to a random node in the pool.
 */
class Redis_Cluster extends Abstract_Aggregate_Connection implements Cluster_Interface, IteratorAggregate, Countable
{
    public $use_cluster_slots = true;
    /**
     * @var NodeConnectionInterface[]
     */
    private $pool = [];
    private $slots = [];
    private $slotmap;
    private $strategy;
    private $connections;
    private $retry_limit = 5;
    private $retry_interval = 10;
    /**
     * @var int
     */
    private $read_timeout = 1000;
    /**
     * @var ParametersInterface
     */
    private $connection_parameters;
    /**
     * @param FactoryInterface       $connections Optional connection factory.
     * @param StrategyInterface|null $strategy    Optional cluster strategy.
     * @param int|null               $readTimeout Optional read timeout
     */
    public function __construct(Factory_Interface $connections, Parameters_Interface $parameters, ?Strategy_Interface $strategy = null, ?int $read_timeout = null)
    {
        $this->connections = $connections;
        $this->connection_parameters = $parameters;
        $this->strategy = $strategy ?: new Redis_Cluster_Strategy();
        $this->slotmap = new Slot_Map();
        if (!is_null($read_timeout)) {
            $this->read_timeout = $read_timeout;
        }
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
     * Sets the initial retry interval (milliseconds).
     *
     * @param int $retryInterval Milliseconds between retries.
     */
    public function set_retry_interval($retry_interval): void
    {
        $this->retry_interval = (int) $retry_interval;
    }
    /**
     * Returns the retry interval (milliseconds).
     *
     * @return int Milliseconds between retries.
     */
    public function get_retry_interval(): int
    {
        return (int) $this->retry_interval;
    }
    /**
     * {@inheritdoc}
     */
    public function is_connected(): bool
    {
        foreach ($this->pool as $connection) {
            if ($connection->is_connected()) {
                return true;
            }
        }
        return false;
    }
    /**
     * {@inheritdoc}
     */
    public function connect(): void
    {
        foreach ($this->pool as $connection) {
            $connection->connect();
        }
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
     * {@inheritdoc}
     */
    public function add(Node_Connection_Interface $connection): void
    {
        $this->pool[(string) $connection] = $connection;
        $this->slotmap->reset();
    }
    /**
     * {@inheritdoc}
     */
    public function remove(Node_Connection_Interface $connection): bool
    {
        if (false !== $id = array_search($connection, $this->pool, true)) {
            $this->slotmap->reset();
            $this->slots = array_diff($this->slots, [$connection]);
            unset($this->pool[$id]);
            return true;
        }
        return false;
    }
    /**
     * Removes a connection instance by using its identifier.
     *
     * @param string $connectionID Connection identifier.
     *
     * @return bool True if the connection was in the pool.
     */
    public function remove_by_id($connection_id): bool
    {
        if (isset($this->pool[$connection_id])) {
            $this->slotmap->reset();
            $this->slots = array_diff($this->slots, [$connection_id]);
            unset($this->pool[$connection_id]);
            return true;
        }
        return false;
    }
    /**
     * Generates the current slots map by guessing the cluster configuration out
     * of the connection parameters of the connections in the pool.
     *
     * Generation is based on the same algorithm used by Redis to generate the
     * cluster, so it is most effective when all of the connections supplied on
     * initialization have the "slots" parameter properly set accordingly to the
     * current cluster configuration.
     */
    public function build_slot_map(): void
    {
        $this->slotmap->reset();
        foreach ($this->pool as $connection_id => $connection) {
            $parameters = $connection->get_parameters();
            if (!isset($parameters->slots)) {
                continue;
            }
            foreach (explode(',', $parameters->slots) as $slot_range) {
                $slots = explode('-', $slot_range, 2);
                if (!isset($slots[1])) {
                    $slots[1] = $slots[0];
                }
                $this->slotmap->set_slots($slots[0], $slots[1], $connection_id);
            }
        }
    }
    /**
     * Queries the specified node of the cluster to fetch the updated slots map.
     *
     * When the connection fails, this method tries to execute the same command
     * on a different connection picked at random from the pool of known nodes,
     * up until the retry limit is reached.
     *
     * @param NodeConnectionInterface $connection Connection to a node of the cluster.
     *
     * @return mixed
     */
    private function query_cluster_node_for_slot_map(Node_Connection_Interface $connection)
    {
        // Backward-compatible hardcoded retry
        $retry = new Retry(new Exponential_Backoff($this->retry_interval * 1000, -1), $this->retry_limit, [Connection_Exception::class]);
        $command = Raw_Command::create('CLUSTER', 'SLOTS');
        $do_callback = static function () use (&$connection, $command) {
            return $connection->execute_command($command);
        };
        $fail_callback = function (Connection_Exception $exception) use (&$connection): void {
            $connection = $exception->get_connection();
            $connection->disconnect();
            $this->remove($connection);
            if (!$connection = $this->get_random_connection()) {
                throw new Client_Exception('No connections left in the pool for `CLUSTER SLOTS`');
            }
        };
        return $retry->call_with_retry($do_callback, $fail_callback);
    }
    /**
     * Generates an updated slots map fetching the cluster configuration using
     * the CLUSTER SLOTS command against the specified node or a random one from
     * the pool.
     *
     * @param NodeConnectionInterface|null $connection Optional connection instance.
     */
    public function ask_slot_map(?Node_Connection_Interface $connection = null): void
    {
        if (!$connection && !$connection = $this->get_random_connection()) {
            return;
        }
        $this->slotmap->reset();
        $response = $this->query_cluster_node_for_slot_map($connection);
        foreach ($response as $slots) {
            // We only support master servers for now, so we ignore subsequent
            // elements in the $slots array identifying slaves.
            [$start, $end, $master] = $slots;
            if ($master[0] === '') {
                $this->slotmap->set_slots($start, $end, (string) $connection);
            } else {
                $this->slotmap->set_slots($start, $end, "{$master[0]}:{$master[1]}");
            }
        }
    }
    /**
     * Guesses the correct node associated to a given slot using a precalculated
     * slots map, falling back to the same logic used by Redis to initialize a
     * cluster (best-effort).
     *
     * @param int $slot Slot index.
     *
     * @return string Connection ID.
     */
    protected function guess_node($slot)
    {
        if (!$this->pool) {
            throw new Client_Exception('No connections available in the pool');
        }
        if ($this->slotmap->is_empty()) {
            $this->build_slot_map();
        }
        if ($node = $this->slotmap[$slot]) {
            return $node;
        }
        $count = count($this->pool);
        $index = min((int) ($slot / (int) (16384 / $count)), $count - 1);
        $nodes = array_keys($this->pool);
        return $nodes[$index];
    }
    /**
     * Creates a new connection instance from the given connection ID.
     *
     * @param string $connectionID Identifier for the connection.
     *
     * @return NodeConnectionInterface
     */
    protected function create_connection($connection_id)
    {
        $separator = strrpos($connection_id, ':');
        return $this->connections->create(['host' => substr($connection_id, 0, $separator), 'port' => substr($connection_id, $separator + 1)]);
    }
    /**
     * {@inheritdoc}
     */
    public function get_connection_by_command(Command_Interface $command)
    {
        $slot = $this->strategy->get_slot($command);
        if (!isset($slot)) {
            throw new Not_Supported_Exception("Cannot use '{$command->get_id()}' with redis-cluster.");
        }
        return $this->slots[$slot] ?? $this->get_connection_by_slot($slot);
    }
    /**
     * Returns the connection currently associated to a given slot.
     *
     * @param int $slot Slot index.
     *
     * @return NodeConnectionInterface
     * @throws OutOfBoundsException
     */
    public function get_connection_by_slot($slot)
    {
        if (!Slot_Map::is_valid($slot)) {
            throw new OutOfBoundsException("Invalid slot [{$slot}].");
        }
        if (isset($this->slots[$slot])) {
            return $this->slots[$slot];
        }
        $connection_id = $this->guess_node($slot);
        if (!$connection = $this->get_connection_by_id($connection_id)) {
            $connection = $this->create_connection($connection_id);
            $this->pool[$connection_id] = $connection;
        }
        return $this->slots[$slot] = $connection;
    }
    /**
     * {@inheritdoc}
     */
    public function get_connection_by_id($connection_id)
    {
        return $this->pool[$connection_id] ?? null;
    }
    /**
     * Returns a random connection from the pool.
     *
     * @return NodeConnectionInterface|null
     */
    protected function get_random_connection()
    {
        if (!$this->pool) {
            return null;
        }
        return $this->pool[array_rand($this->pool)];
    }
    /**
     * Permanently associates the connection instance to a new slot.
     * The connection is added to the connections pool if not yet included.
     *
     * @param NodeConnectionInterface $connection Connection instance.
     * @param int                     $slot       Target slot index.
     */
    protected function move(Node_Connection_Interface $connection, $slot)
    {
        $this->pool[(string) $connection] = $connection;
        $this->slots[(int) $slot] = $connection;
        $this->slotmap[(int) $slot] = $connection;
    }
    /**
     * Handles -ERR responses returned by Redis.
     *
     * @param CommandInterface       $command Command that generated the -ERR response.
     * @param ErrorResponseInterface $error   Redis error response object.
     *
     * @return mixed
     */
    protected function on_error_response(Command_Interface $command, Error_Response_Interface $error)
    {
        $details = explode(' ', $error->get_message(), 2);
        switch ($details[0]) {
            case 'MOVED':
                return $this->on_moved_response($command, $details[1]);
            case 'ASK':
                return $this->on_ask_response($command, $details[1]);
            default:
                return $error;
        }
    }
    /**
     * Handles -MOVED responses by executing again the command against the node
     * indicated by the Redis response.
     *
     * @param CommandInterface $command Command that generated the -MOVED response.
     * @param string           $details Parameters of the -MOVED response.
     *
     * @return mixed
     */
    protected function on_moved_response(Command_Interface $command, $details)
    {
        [$slot, $connection_id] = explode(' ', $details, 2);
        // Handle connection ID in the form of "IP:port (details about exception)"
        // by trimming everything after first space (including the space)
        $start_position_of_extra_details = strpos($connection_id, ' ');
        if ($start_position_of_extra_details !== false) {
            $connection_id = substr($connection_id, 0, $start_position_of_extra_details);
        }
        if (!$connection = $this->get_connection_by_id($connection_id)) {
            $connection = $this->create_connection($connection_id);
        }
        if ($this->use_cluster_slots) {
            $this->ask_slot_map($connection);
        }
        $this->move($connection, $slot);
        return $this->execute_command($command);
    }
    /**
     * Handles -ASK responses by executing again the command against the node
     * indicated by the Redis response.
     *
     * @param CommandInterface $command Command that generated the -ASK response.
     * @param string           $details Parameters of the -ASK response.
     *
     * @return mixed
     */
    protected function on_ask_response(Command_Interface $command, $details)
    {
        [$slot, $connection_id] = explode(' ', $details, 2);
        if (!$connection = $this->get_connection_by_id($connection_id)) {
            $connection = $this->create_connection($connection_id);
        }
        $connection->execute_command(Raw_Command::create('ASKING'));
        return $connection->execute_command($command);
    }
    /**
     * Ensures that a command is executed one more time on connection failure.
     *
     * The connection to the node that generated the error is evicted from the
     * pool before trying to fetch an updated slots map from another node. If
     * the new slots map points to an unreachable server the client gives up and
     * throws the exception as the nodes participating in the cluster may still
     * have to agree that something changed in the configuration of the cluster.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $method  Actual method.
     *
     * @return mixed
     * @throws Throwable
     */
    private function retry_command_on_failure(Command_Interface $command, string $method)
    {
        if ($this->connection_parameters->is_disabled_retry() || $this->connections instanceof Relay_Factory) {
            // Override default parameters, for backward-compatibility
            // with current behaviour
            $retry = new Retry(new Exponential_Backoff($this->retry_interval * 1000, -1), $this->retry_limit);
        } else {
            $retry = $this->connection_parameters->retry;
        }
        $retry->update_catchable_exceptions([Server_Exception::class]);
        $do_callback = function () use ($command, $method) {
            $response = $this->get_connection_by_command($command)->{$method}($command);
            if ($response instanceof Error_Response) {
                $message = $response->get_message();
                if (strpos($message, 'CLUSTERDOWN') !== false) {
                    throw new Server_Exception($message);
                }
            }
            return $response;
        };
        return $retry->call_with_retry($do_callback, function (Throwable $e): void {
            $this->on_fail_callback($e);
        });
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
        $response = $this->retry_command_on_failure($command, __FUNCTION__);
        if ($response instanceof Error_Response_Interface) {
            return $this->on_error_response($command, $response);
        }
        return $response;
    }
    /**
     * {@inheritdoc}
     */
    public function execute_command_on_each_node(Command_Interface $command): array
    {
        $responses = [];
        foreach ($this->pool as $connection) {
            $responses[] = $connection->execute_command($command);
        }
        return $responses;
    }
    /**
     * @return int
     */
    #[Return_Type_Will_Change]
    public function count()
    {
        return count($this->pool);
    }
    /**
     * @return Traversable<string, NodeConnectionInterface>
     */
    #[Return_Type_Will_Change]
    public function getIterator()
    {
        if ($this->slotmap->is_empty()) {
            $this->use_cluster_slots ? $this->ask_slot_map() : $this->build_slot_map();
        }
        $connections = [];
        foreach ($this->slotmap->get_nodes() as $node) {
            if (!$connection = $this->get_connection_by_id($node)) {
                $this->add($connection = $this->create_connection($node));
            }
            $connections[] = $connection;
        }
        return new ArrayIterator($connections);
    }
    /**
     * Returns the underlying slot map.
     *
     * @return SlotMap
     */
    public function get_slot_map()
    {
        return $this->slotmap;
    }
    /**
     * {@inheritDoc}
     */
    public function get_cluster_strategy(): Strategy_Interface
    {
        return $this->strategy;
    }
    /**
     * Returns the underlying connection factory used to create new connection
     * instances to Redis nodes indicated by redis-cluster.
     *
     * @return FactoryInterface
     */
    public function get_connection_factory()
    {
        return $this->connections;
    }
    /**
     * Enables automatic fetching of the current slots map from one of the nodes
     * using the CLUSTER SLOTS command. This option is enabled by default as
     * asking the current slots map to Redis upon -MOVED responses may reduce
     * overhead by eliminating the trial-and-error nature of the node guessing
     * procedure, mostly when targeting many keys that would end up in a lot of
     * redirections.
     *
     * The slots map can still be manually fetched using the askSlotMap()
     * method whether or not this option is enabled.
     *
     * @param bool $value Enable or disable the use of CLUSTER SLOTS.
     */
    public function use_cluster_slots($value): void
    {
        $this->use_cluster_slots = (bool) $value;
    }
    /**
     * {@inheritdoc}
     */
    public function get_parameters(): ?Parameters_Interface
    {
        return $this->connection_parameters;
    }
    /**
     * Loop over connections until there's data to read.
     *
     * @return mixed
     */
    public function read()
    {
        while (true) {
            foreach ($this->pool as $connection) {
                if ($connection->has_data_to_read()) {
                    return $connection->read();
                }
            }
            usleep($this->read_timeout);
        }
    }
    /**
     * Handle exceptions.
     */
    private function on_fail_callback(Throwable $exception): void
    {
        if ($exception instanceof Connection_Exception) {
            $connection = $exception->get_connection();
            if ($connection) {
                $connection->disconnect();
                $this->remove($connection);
            }
            if ($this->use_cluster_slots) {
                $this->ask_slot_map();
            }
        }
        if ($exception instanceof Timeout_Exception) {
            $connection = $exception->get_connection();
            if ($connection) {
                $connection->disconnect();
            }
        }
    }
}