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

use Predis\Communication_Exception;
use Predis\Connection\Cluster\Cluster_Interface;
use Predis\Connection\Connection_Interface;
use Predis\Connection\Node_Connection_Interface;
use Predis\Not_Supported_Exception;
use SplQueue;
/**
 * Command pipeline that does not throw exceptions on connection errors, but
 * returns the exception instances as the rest of the response elements.
 */
class Connection_Error_Proof extends Pipeline
{
    /**
     * {@inheritdoc}
     */
    protected function get_connection()
    {
        return $this->get_client()->get_connection();
    }
    /**
     * {@inheritdoc}
     */
    protected function execute_pipeline(Connection_Interface $connection, SplQueue $commands)
    {
        if ($connection instanceof Node_Connection_Interface) {
            return $this->execute_single_node($connection, $commands);
        }
        if ($connection instanceof Cluster_Interface) {
            return $this->execute_cluster($connection, $commands);
        }
        $class = get_class($connection);
        throw new Not_Supported_Exception("The connection class '{$class}' is not supported.");
    }
    /**
     * {@inheritdoc}
     * @return mixed[]
     */
    protected function execute_single_node(Node_Connection_Interface $connection, SplQueue $commands): array
    {
        $responses = [];
        $size_of_pipe = count($commands);
        $buffer = '';
        foreach ($commands as $command) {
            $buffer .= $command->serialize_command();
        }
        try {
            $connection->write($buffer);
        } catch (Communication_Exception $exception) {
            return array_fill(0, $size_of_pipe, $exception);
        }
        for ($i = 0; $i < $size_of_pipe; ++$i) {
            $command = $commands->dequeue();
            try {
                $responses[$i] = $connection->read_response($command);
            } catch (Communication_Exception $exception) {
                $add = count($commands) - count($responses);
                $responses = array_merge($responses, array_fill(0, $add, $exception));
                break;
            }
        }
        return $responses;
    }
    /**
     * {@inheritdoc}
     * @return mixed[]
     */
    protected function execute_cluster(Cluster_Interface $connection, SplQueue $commands): array
    {
        $responses = [];
        $size_of_pipe = count($commands);
        $exceptions = [];
        foreach ($commands as $command) {
            $node_connection = $connection->get_connection_by_command($command);
            $node_connection->write($command->serialize_command());
        }
        for ($i = 0; $i < $size_of_pipe; ++$i) {
            $command = $commands->dequeue();
            $cmd_connection = $connection->get_connection_by_command($command);
            $connection_hash = spl_object_hash($cmd_connection);
            if (isset($exceptions[$connection_hash])) {
                $responses[$i] = $exceptions[$connection_hash];
                continue;
            }
            try {
                $responses[$i] = $cmd_connection->read_response($command);
            } catch (Communication_Exception $exception) {
                $responses[$i] = $exception;
                $exceptions[$connection_hash] = $exception;
            }
        }
        return $responses;
    }
}