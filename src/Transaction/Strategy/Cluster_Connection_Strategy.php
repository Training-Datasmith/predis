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
namespace Predis\Transaction\Strategy;

use Predis\Command\Command_Interface;
use Predis\Command\Redis\DISCARD;
use Predis\Command\Redis\EXEC;
use Predis\Command\Redis\MULTI;
use Predis\Command\Redis\UNWATCH;
use Predis\Command\Redis\WATCH;
use Predis\Connection\Cluster\Cluster_Interface;
use Predis\Response\Error;
use Predis\Response\Status;
use Predis\Transaction\Exception\Transaction_Exception;
use Predis\Transaction\Multi_Exec_State;
use Relay\Relay;
use SplQueue;
class Cluster_Connection_Strategy implements Strategy_Interface
{
    /**
     * @var ClusterInterface
     */
    private $connection;
    /**
     * Server-matching slot of the current transaction.
     *
     * @var ?int
     */
    private $slot;
    /**
     * In cluster environment it needs to be queued to ensure
     * that all commands will point to the same node.
     *
     * @var SplQueue
     */
    private $commands_queue;
    /**
     * Shows if transaction context was initialized.
     *
     * @var bool
     */
    private $is_initialized = false;
    /**
     * @var \Predis\Cluster\StrategyInterface
     */
    private $cluster_strategy;
    /**
     * @var MultiExecState
     */
    private $state;
    public function __construct(Cluster_Interface $connection, Multi_Exec_State $state)
    {
        $this->commands_queue = new SplQueue();
        $this->connection = $connection;
        $this->state = $state;
        $this->cluster_strategy = $this->connection->get_cluster_strategy();
    }
    /**
     * {@inheritDoc}
     */
    public function execute_command(Command_Interface $command)
    {
        if (!$this->is_initialized) {
            throw new Transaction_Exception('Transaction context should be initialized first');
        }
        $command_slot = $this->cluster_strategy->get_slot($command);
        if (null === $this->slot) {
            $this->slot = $command_slot;
        }
        if (null === $command_slot && null !== $this->slot) {
            $command->set_slot($this->slot);
        }
        if (is_int($command_slot) && $command_slot !== $this->slot) {
            return new Error('To be able to execute a transaction against cluster, all commands should operate on the same hash slot');
        }
        $this->commands_queue->enqueue($command);
        return new Status('QUEUED');
    }
    /**
     * {@inheritDoc}
     */
    public function initialize_transaction(): bool
    {
        if ($this->is_initialized) {
            return true;
        }
        $this->commands_queue->enqueue(new MULTI());
        $this->is_initialized = true;
        return true;
    }
    /**
     * {@inheritDoc}
     */
    public function execute_transaction()
    {
        if (!$this->is_initialized) {
            throw new Transaction_Exception('Transaction context should be initialized first');
        }
        $exec = new EXEC();
        /** @var MULTI $multi */
        $multi = $this->commands_queue->dequeue();
        $multi_resp = $this->set_slot_and_execute($multi);
        // Begin transaction
        if ('OK' != $multi_resp && !$multi_resp instanceof Relay) {
            $this->slot = null;
            return null;
        }
        // Transaction body
        while (!$this->commands_queue->is_empty()) {
            /** @var CommandInterface $command */
            $command = $this->commands_queue->dequeue();
            $command_resp = $this->set_slot_and_execute($command);
            if ('QUEUED' != $command_resp && !$command_resp instanceof Relay) {
                $this->slot = null;
                return null;
            }
        }
        // Execute transaction
        $exec = $this->set_slot_and_execute($exec);
        $this->slot = null;
        return $exec;
    }
    /**
     * {@inheritDoc}
     */
    public function multi()
    {
        $response = $this->set_slot_and_execute(new MULTI());
        if ('OK' == $response) {
            $this->is_initialized = true;
        }
        return $response;
    }
    /**
     * {@inheritDoc}
     */
    public function watch(array $keys)
    {
        if (!$this->cluster_strategy->check_same_slot_for_keys($keys)) {
            throw new Transaction_Exception('WATCHed keys should point to the same hash slot');
        }
        $this->slot = $this->cluster_strategy->get_slot_by_key($keys[0]);
        $watch = new WATCH();
        $watch->set_arguments($keys);
        $response = 'OK' == $this->set_slot_and_execute($watch);
        if ($this->state->check(Multi_Exec_State::CAS)) {
            $this->initialize_transaction();
        }
        return $response;
    }
    /**
     * {@inheritDoc}
     */
    public function discard()
    {
        return $this->set_slot_and_execute(new DISCARD());
    }
    /**
     * {@inheritDoc}
     */
    public function unwatch()
    {
        return $this->set_slot_and_execute(new UNWATCH());
    }
    /**
     * Assigns slot to a command and executes.
     *
     * @return mixed
     */
    private function set_slot_and_execute(Command_Interface $command)
    {
        if (null !== $this->slot) {
            $command->set_slot($this->slot);
        }
        return $this->connection->execute_command($command);
    }
}