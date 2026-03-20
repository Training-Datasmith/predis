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

use Predis\Command\Command_Interface;
/**
 * Defines a connection object used to communicate with one or multiple
 * Redis servers.
 */
interface Connection_Interface
{
    /**
     * Opens the connection to Redis.
     */
    public function connect();
    /**
     * Closes the connection to Redis.
     */
    public function disconnect();
    /**
     * Checks if the connection to Redis is considered open.
     *
     * @return bool
     */
    public function is_connected();
    /**
     * Writes the request for the given command over the connection.
     *
     * @param CommandInterface $command Command instance.
     */
    public function write_request(Command_Interface $command);
    /**
     * Reads the response to the given command from the connection.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return mixed
     */
    public function read_response(Command_Interface $command);
    /**
     * Performs a write operation over the stream of the buffer containing a
     * command serialized with the Redis wire protocol.
     */
    public function write(string $buffer): void;
    /**
     * Writes a request for the given command over the connection and reads back
     * the response returned by Redis.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return mixed
     */
    public function execute_command(Command_Interface $command);
    /**
     * Returns the parameters used to initialize the connection.
     *
     * @return ParametersInterface
     */
    public function get_parameters();
}