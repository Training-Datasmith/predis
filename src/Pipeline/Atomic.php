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

use Predis\Client_Exception;
use Predis\Client_Interface;
use Predis\Command\Command;
use Predis\Command\Command_Interface;
use Predis\Communication_Exception;
use Predis\Connection\Connection_Interface;
use Predis\Connection\Node_Connection_Interface;
use Predis\Response\Error_Interface as ErrorResponseInterface;
use Predis\Response\Response_Interface;
use Predis\Response\Server_Exception;
use SplQueue;
use Throwable;
/**
 * Command pipeline wrapped into a MULTI / EXEC transaction.
 */
class Atomic extends Pipeline
{
    /**
     * {@inheritdoc}
     */
    public function __construct(Client_Interface $client)
    {
        if (!$client->get_command_factory()->supports('multi', 'exec', 'discard')) {
            throw new Client_Exception("'MULTI', 'EXEC' and 'DISCARD' are not supported by the current command factory.");
        }
        parent::__construct($client);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_connection()
    {
        $connection = $this->get_client()->get_connection();
        if (!$connection instanceof Node_Connection_Interface) {
            $class = self::class;
            throw new Client_Exception("The class '{$class}' does not support aggregate connections.");
        }
        return $connection;
    }
    /**
     * {@inheritdoc}
     * @return mixed[]
     */
    protected function execute_pipeline(Connection_Interface $connection, SplQueue $commands): array
    {
        $command_factory = $this->get_client()->get_command_factory();
        $retry = $connection->get_parameters()->retry;
        $this->execute_command_with_retry($connection, $command_factory->create('multi'));
        $retry->call_with_retry(function () use ($connection, $commands): void {
            $this->queue_pipeline($connection, $commands);
        }, static function (Throwable $exception): void {
            if ($exception instanceof Communication_Exception) {
                $exception->get_connection()->disconnect();
            }
        });
        $executed = $this->execute_command_with_retry($connection, $command_factory->create('exec'));
        if (!isset($executed)) {
            throw new Client_Exception('The underlying transaction has been aborted by the server.');
        }
        if (count($executed) !== count($commands)) {
            $expected = count($commands);
            $received = count($executed);
            throw new Client_Exception("Invalid number of responses [expected {$expected}, received {$received}].");
        }
        $responses = [];
        $size_of_pipe = count($commands);
        $exceptions = $this->throw_server_exceptions();
        $protocol_version = (int) $connection->get_parameters()->protocol;
        for ($i = 0; $i < $size_of_pipe; ++$i) {
            $command = $commands->dequeue();
            $response = $executed[$i];
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
            unset($executed[$i]);
        }
        return $responses;
    }
    /**
     * @return void
     * @throws Throwable
     */
    protected function queue_pipeline(Connection_Interface $connection, SplQueue $commands)
    {
        $command_factory = $this->get_client()->get_command_factory();
        $this->write_to_single_node($connection, $commands);
        foreach ($commands as $command) {
            $response = $connection->read_response($command);
            if ($response instanceof Error_Response_Interface) {
                $this->execute_command_with_retry($connection, $command_factory->create('discard'));
                throw new Server_Exception($response->get_message());
            }
        }
    }
    /**
     * @param  Command             $command
     * @return mixed
     * @throws Throwable
     */
    protected function execute_command_with_retry(Connection_Interface $connection, Command_Interface $command)
    {
        $retry = $connection->get_parameters()->retry;
        return $retry->call_with_retry(static function () use ($connection, $command) {
            return $connection->execute_command($command);
        }, static function (Throwable $e): void {
            if ($e instanceof Communication_Exception) {
                $e->get_connection()->disconnect();
            }
        });
    }
}