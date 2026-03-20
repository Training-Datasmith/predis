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
use Predis\Cluster\Predis_Strategy;
use Predis\Cluster\Strategy_Interface;
use Predis\Command\Command_Interface;
use Predis\Connection\Abstract_Aggregate_Connection;
use Predis\Connection\Node_Connection_Interface;
use Predis\Connection\Parameters_Interface;
use Predis\Not_Supported_Exception;
use Return_Type_Will_Change;
use Traversable;
/**
 * Abstraction for a cluster of aggregate connections to various Redis servers
 * implementing client-side sharding based on pluggable distribution strategies.
 */
class Predis_Cluster extends Abstract_Aggregate_Connection implements Cluster_Interface, IteratorAggregate, Countable
{
    /**
     * @var NodeConnectionInterface[]
     */
    private $pool = [];
    /**
     * @var NodeConnectionInterface[]
     */
    private $aliases = [];
    /**
     * @var StrategyInterface
     */
    private $strategy;
    /**
     * @var \Predis\Cluster\Distributor\DistributorInterface
     */
    private $distributor;
    /**
     * @var ParametersInterface
     */
    private $connection_parameters;
    /**
     * @param StrategyInterface|null $strategy   Optional cluster strategy.
     */
    public function __construct(Parameters_Interface $parameters, ?Strategy_Interface $strategy = null)
    {
        $this->connection_parameters = $parameters;
        $this->strategy = $strategy ?: new Predis_Strategy();
        $this->distributor = $this->strategy->get_distributor();
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
        $parameters = $connection->get_parameters();
        $this->pool[(string) $connection] = $connection;
        if (isset($parameters->alias)) {
            $this->aliases[$parameters->alias] = $connection;
        }
        $this->distributor->add($connection, $parameters->weight);
    }
    /**
     * {@inheritdoc}
     */
    public function remove(Node_Connection_Interface $connection): bool
    {
        if (false !== $id = array_search($connection, $this->pool, true)) {
            unset($this->pool[$id]);
            $this->distributor->remove($connection);
            if ($this->aliases && $alias = $connection->get_parameters()->alias) {
                unset($this->aliases[$alias]);
            }
            return true;
        }
        return false;
    }
    /**
     * {@inheritdoc}
     */
    public function get_connection_by_command(Command_Interface $command)
    {
        $slot = $this->strategy->get_slot($command);
        if (!isset($slot)) {
            throw new Not_Supported_Exception("Cannot use '{$command->get_id()}' over clusters of connections.");
        }
        return $this->distributor->get_by_slot($slot);
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
     * Retrieves a connection instance by slot.
     *
     * @param string $slot Slot name.
     *
     * @return NodeConnectionInterface|null
     */
    public function get_connection_by_slot($slot)
    {
        return $this->distributor->get_by_slot($slot);
    }
    /**
     * Retrieves a connection instance from the cluster using a key.
     *
     * @param string $key Key string.
     *
     * @return NodeConnectionInterface
     */
    public function get_connection_by_key($key)
    {
        $hash = $this->strategy->get_slot_by_key($key);
        return $this->distributor->get_by_slot($hash);
    }
    /**
     * {@inheritDoc}
     */
    public function get_cluster_strategy(): Strategy_Interface
    {
        return $this->strategy;
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
        return new ArrayIterator($this->pool);
    }
    /**
     * {@inheritdoc}
     */
    public function write_request(Command_Interface $command): void
    {
        $this->get_connection_by_command($command)->write_request($command);
    }
    /**
     * {@inheritdoc}
     */
    public function read_response(Command_Interface $command)
    {
        return $this->get_connection_by_command($command)->read_response($command);
    }
    /**
     * {@inheritdoc}
     */
    public function execute_command(Command_Interface $command)
    {
        return $this->get_connection_by_command($command)->execute_command($command);
    }
    /**
     * {@inheritdoc}
     */
    public function get_parameters(): Parameters_Interface
    {
        return $this->connection_parameters;
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
}