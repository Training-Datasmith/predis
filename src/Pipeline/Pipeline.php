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
namespace Predis\Pipeline;

use Exception;
use InvalidArgumentException;
use Predis\Client_Context_Interface;
use Predis\Client_Exception;
use Predis\Client_Interface;
use Predis\Command\Command_Interface;
use Predis\Communication_Exception;
use Predis\Connection\Aggregate_Connection_Interface;
use Predis\Connection\Cluster\Redis_Cluster;
use Predis\Connection\Connection_Exception;
use Predis\Connection\Connection_Interface;
use Predis\Connection\Replication\Replication_Interface;
use Predis\Response\Error_Interface as ErrorResponseInterface;
use Predis\Response\Response_Interface;
use Predis\Response\Server_Exception;
use Predis\Timeout_Exception;
use SplQueue;
use Throwable;
/**
 * Implementation of a command pipeline in which write and read operations of
 * Redis commands are pipelined to alleviate the effects of network round-trips.
 *
 * {@inheritdoc}
 */
class Pipeline implements Client_Context_Interface
{
    protected $client;
    private $pipeline;
    private $responses = [];
    private $running = false;
    /**
     * @param ClientInterface $client Client instance used by the context.
     */
    public function __construct(Client_Interface $client)
    {
        $this->client = $client;
        $this->pipeline = new SplQueue();
    }
    /**
     * Queues a command into the pipeline buffer.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return $this
     */
    public function __call($method, $arguments)
    {
        $command = $this->client->create_command($method, $arguments);
        $this->record_command($command);
        return $this;
    }
    /**
     * Queues a command instance into the pipeline buffer.
     *
     * @param CommandInterface $command Command to be queued in the buffer.
     */
    protected function record_command(Command_Interface $command)
    {
        $this->pipeline->enqueue($command);
    }
    /**
     * Queues a command instance into the pipeline buffer.
     *
     * @param CommandInterface $command Command instance to be queued in the buffer.
     *
     * @return $this
     */
    public function execute_command(Command_Interface $command): self
    {
        $this->record_command($command);
        return $this;
    }
    /**
     * Throws an exception on -ERR responses returned by Redis.
     *
     * @param ConnectionInterface    $connection Redis connection that returned the error.
     * @param ErrorResponseInterface $response   Instance of the error response.
     *
     * @throws ServerException
     */
    protected function exception(Connection_Interface $connection, Error_Response_Interface $response)
    {
        $connection->disconnect();
        $message = $response->get_message();
        throw new Server_Exception($message);
    }
    /**
     * Returns the underlying connection to be used by the pipeline.
     *
     * @return ConnectionInterface
     */
    protected function get_connection()
    {
        $connection = $this->get_client()->get_connection();
        if ($connection instanceof Replication_Interface) {
            $connection->switch_to_master();
        }
        return $connection;
    }
    /**
     * Implements the logic to flush the queued commands and read the responses
     * from the current connection.
     *
     * @param ConnectionInterface $connection Current connection instance.
     * @param SplQueue            $commands   Queued commands.
     *
     * @return array
     * @throws Throwable
     */
    protected function execute_pipeline(Connection_Interface $connection, SplQueue $commands)
    {
        $retry = $connection->get_parameters()->retry;
        $backup_queue = $this->create_deep_clone_queue($commands);
        return $retry->call_with_retry(function () use ($connection, &$commands): array {
            return $this->execute_pipeline_internal($connection, $commands);
        }, function (Throwable $e) use (&$commands, $backup_queue, $connection): void {
            if (!$e instanceof Communication_Exception) {
                throw $e;
            }
            if ($connection instanceof Aggregate_Connection_Interface) {
                $this->on_aggregate_connection_fail_callback($connection, $e);
            } else {
                $connection = $e->get_connection();
                $connection->disconnect();
            }
            // In case of error whole pipeline should be retried
            // So we need to write all original commands again
            $commands = $this->create_deep_clone_queue($backup_queue);
        });
    }
    /**
     * @throws ServerException
     * @throws Throwable
     */
    protected function execute_pipeline_internal(Connection_Interface $connection, SplQueue $commands): array
    {
        $responses = [];
        $exceptions = $this->throw_server_exceptions();
        $protocol_version = (int) $connection->get_parameters()->protocol;
        if ($connection instanceof Aggregate_Connection_Interface) {
            $this->write_to_multi_node($connection, $commands);
        } else {
            $this->write_to_single_node($connection, $commands);
        }
        while (!$commands->is_empty()) {
            $command = $commands->dequeue();
            if ($connection instanceof Aggregate_Connection_Interface) {
                $response = $connection->get_connection_by_command($command)->read_response($command);
            } else {
                $response = $connection->read_response($command);
            }
            if (!$response instanceof Response_Interface) {
                if ($protocol_version === 2) {
                    $responses[] = $command->parse_response($response);
                } else {
                    $responses[] = $command->parse_resp3response($response);
                }
            } elseif ($response instanceof Error_Response_Interface && $exceptions) {
                $this->exception($connection, $response);
            } else {
                $responses[] = $response;
            }
        }
        return $responses;
    }
    /**
     * Creates a deep copy of commands queue for backup.
     */
    private function create_deep_clone_queue(SplQueue $queue): SplQueue
    {
        $new = new SplQueue();
        foreach ($queue as $command) {
            $new->enqueue(clone $command);
        }
        return $new;
    }
    /**
     * Writes pipelined commands to single node connection.
     *
     * @return void
     * @throws Throwable
     */
    protected function write_to_single_node(Connection_Interface $connection, SplQueue $commands)
    {
        $buffer = '';
        foreach ($commands as $command) {
            $buffer .= $command->serialize_command();
        }
        $connection->write($buffer);
    }
    /**
     * Writes pipelined commands to multi node connection.
     *
     * @return void
     * @throws Throwable
     */
    protected function write_to_multi_node(Aggregate_Connection_Interface $connection, SplQueue $commands)
    {
        $connection->get_parameters()->retry;
        foreach ($commands as $command) {
            $node_connection = $connection->get_connection_by_command($command);
            $node_connection->write($command->serialize_command());
        }
    }
    /**
     * Flushes the buffer holding all of the commands queued so far.
     *
     * @param bool $send Specifies if the commands in the buffer should be sent to Redis.
     *
     * @return $this
     */
    public function flush_pipeline($send = true): self
    {
        if ($send && !$this->pipeline->is_empty()) {
            $responses = $this->execute_pipeline($this->get_connection(), $this->pipeline);
            $this->responses = array_merge($this->responses, $responses);
        } else {
            $this->pipeline = new SplQueue();
        }
        return $this;
    }
    /**
     * Marks the running status of the pipeline.
     *
     * @param bool $bool Sets the running status of the pipeline.
     *
     * @throws ClientException
     */
    private function set_running(bool $bool): void
    {
        if ($bool && $this->running) {
            throw new Client_Exception('The current pipeline context is already being executed.');
        }
        $this->running = $bool;
    }
    /**
     * Handles the actual execution of the whole pipeline.
     *
     * @param mixed $callable Optional callback for execution.
     *
     * @return array
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function execute($callable = null)
    {
        if ($callable && !is_callable($callable)) {
            throw new InvalidArgumentException('The argument must be a callable object.');
        }
        $exception = null;
        $this->set_running(true);
        try {
            if ($callable) {
                call_user_func($callable, $this);
            }
            $this->flush_pipeline();
        } catch (\Throwable $exception) {
            // NOOP
        }
        $this->set_running(false);
        if ($exception) {
            throw $exception;
        }
        return $this->responses;
    }
    /**
     * Returns if the pipeline should throw exceptions on server errors.
     */
    protected function throw_server_exceptions(): bool
    {
        return (bool) $this->client->get_options()->exceptions;
    }
    /**
     * Returns the underlying client instance used by the pipeline object.
     *
     * @return ClientInterface
     */
    public function get_client()
    {
        return $this->client;
    }
    /**
     * Handle aggregate connection exception.
     *
     * @param  CommunicationException       $e
     */
    private function on_aggregate_connection_fail_callback(Aggregate_Connection_Interface $connection, Throwable $e): void
    {
        if ($e instanceof Connection_Exception) {
            $node_connection = $e->get_connection();
            if ($node_connection) {
                $node_connection->disconnect();
                $connection->remove($node_connection);
            }
            if ($connection instanceof Redis_Cluster) {
                if ($connection->use_cluster_slots) {
                    $connection->ask_slot_map();
                }
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