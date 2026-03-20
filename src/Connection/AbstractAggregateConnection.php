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
namespace Predis\Connection;

use Predis\Command\Command;
use Predis\Command\Command_Interface;
abstract class Abstract_Aggregate_Connection implements Aggregate_Connection_Interface
{
    /**
     * {@inheritDoc}
     */
    abstract public function add(Node_Connection_Interface $connection);
    /**
     * {@inheritDoc}
     */
    abstract public function remove(Node_Connection_Interface $connection);
    /**
     * {@inheritDoc}
     */
    abstract public function get_connection_by_command(Command_Interface $command);
    /**
     * {@inheritDoc}
     */
    abstract public function get_connection_by_id($connection_id);
    /**
     * {@inheritDoc}
     */
    abstract public function connect();
    /**
     * {@inheritDoc}
     */
    abstract public function disconnect();
    /**
     * {@inheritDoc}
     */
    abstract public function is_connected();
    /**
     * {@inheritDoc}
     */
    abstract public function write_request(Command_Interface $command);
    /**
     * {@inheritDoc}
     */
    abstract public function read_response(Command_Interface $command);
    /**
     * {@inheritDoc}
     */
    abstract public function execute_command(Command_Interface $command);
    /**
     * {@inheritDoc}
     */
    abstract public function get_parameters();
    /**
     * {@inheritDoc}
     */
    public function write(string $buffer): void
    {
        $raw_commands = [];
        $exploded_buffer = explode("\r\n", trim($buffer));
        while (!empty($exploded_buffer)) {
            $args_len = (int) explode('*', $exploded_buffer[0])[1];
            $cmd_len = $args_len * 2 + 1;
            $raw_commands[] = array_splice($exploded_buffer, 0, $cmd_len);
        }
        foreach ($raw_commands as $command) {
            $command = implode("\r\n", $command) . "\r\n";
            $command_obj = Command::deserialize_command($command);
            $this->get_connection_by_command($command_obj)->write($command);
        }
    }
}