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
use Predis\Communication_Exception;
use Predis\Connection\Connection_Exception;
use Predis\Connection\Node_Connection_Interface;
use Predis\Connection\Relay_Connection;
use Predis\Connection\Replication\Replication_Interface;
use Predis\Response\Error_Interface;
use Predis\Response\Server_Exception;
use Predis\Timeout_Exception;
use Predis\Transaction\Multi_Exec_State;
use Predis\Transaction\Response\Bypass_Transaction_Response;
use Throwable;
/**
 * Defines strategy for connections that operates on non-distributed hash slots.
 */
abstract class Non_Cluster_Connection_Strategy implements Strategy_Interface
{
    /**
     * @var NodeConnectionInterface|ReplicationInterface
     */
    protected $connection;
    /**
     * @var MultiExecState
     */
    protected $state;
    /**
     * @param NodeConnectionInterface|ReplicationInterface $connection
     */
    public function __construct($connection, Multi_Exec_State $state)
    {
        $this->connection = $connection;
        $this->state = $state;
    }
    /**
     * {@inheritDoc}
     */
    public function initialize_transaction(): bool
    {
        return 'OK' == $this->execute_bypassing_transaction(new MULTI())->get_response();
    }
    /**
     * {@inheritDoc}
     * @throws Throwable
     */
    public function execute_command(Command_Interface $command)
    {
        if ($this->state->is_cas()) {
            return $this->execute_bypassing_transaction($command);
        }
        $retry = $this->connection->get_parameters()->retry;
        return $retry->call_with_retry(function () use ($command) {
            return $this->connection->execute_command($command);
        }, function (Communication_Exception $e): void {
            $this->on_fail_callback($e);
        });
    }
    /**
     * {@inheritDoc}
     */
    public function execute_transaction()
    {
        return $this->execute_bypassing_transaction(new EXEC())->get_response();
    }
    /**
     * {@inheritDoc}
     */
    public function multi()
    {
        return $this->execute_bypassing_transaction(new MULTI())->get_response();
    }
    /**
     * {@inheritDoc}
     */
    public function watch(array $keys)
    {
        $watch = new WATCH();
        $watch->set_arguments($keys);
        return $this->execute_bypassing_transaction($watch)->get_response();
    }
    /**
     * {@inheritDoc}
     * @throws Throwable
     */
    public function unwatch()
    {
        $retry = $this->connection->get_parameters()->retry;
        return $retry->call_with_retry(function () {
            return $this->connection->execute_command(new UNWATCH());
        }, function (Communication_Exception $e): void {
            $this->on_fail_callback($e);
        });
    }
    /**
     * {@inheritDoc}
     */
    public function discard()
    {
        return $this->execute_bypassing_transaction(new DISCARD())->get_response();
    }
    /**
     * Executes a Redis command bypassing the transaction logic.
     *
     * @throws ServerException|Throwable
     */
    protected function execute_bypassing_transaction(Command_Interface $command): Bypass_Transaction_Response
    {
        $retry = $this->connection->get_parameters()->retry;
        try {
            $response = $retry->call_with_retry(function () use ($command) {
                return $this->connection->execute_command($command);
            }, function (Communication_Exception $e): void {
                $this->on_fail_callback($e);
            });
        } catch (Server_Exception $exception) {
            if (!$this->connection instanceof Relay_Connection) {
                throw $exception;
            }
            if (strcasecmp($command->get_id(), 'EXEC') != 0) {
                throw $exception;
            }
            if (!strpos($exception->get_message(), 'RELAY_ERR_REDIS')) {
                throw $exception;
            }
            return new Bypass_Transaction_Response(null);
        }
        if ($response instanceof Error_Interface) {
            throw new Server_Exception($response->get_message());
        }
        return new Bypass_Transaction_Response($response);
    }
    /**
     * Handle communication exception.
     */
    private function on_fail_callback(Communication_Exception $e): void
    {
        $connection = $e->get_connection();
        if ($connection instanceof Node_Connection_Interface) {
            $connection->disconnect();
            return;
        }
        if ($e instanceof Connection_Exception) {
            $node_connection = $e->get_connection();
            if ($node_connection) {
                $node_connection->disconnect();
                $this->connection->remove($node_connection);
            }
        }
        if ($e instanceof Timeout_Exception) {
            $node_connection = $e->get_connection();
            if ($node_connection) {
                $node_connection->disconnect();
            }
        }
    }
}