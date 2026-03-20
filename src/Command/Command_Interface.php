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
namespace Predis\Command;

/**
 * Defines an abstraction representing a Redis command.
 */
interface Command_Interface
{
    /**
     * Returns the ID of the Redis command. By convention, command identifiers
     * must always be uppercase.
     *
     * @return string
     */
    public function get_id();
    /**
     * Assign the specified slot to the command for clustering distribution.
     *
     * @param int $slot Slot ID.
     */
    public function set_slot($slot);
    /**
     * Returns the assigned slot of the command for clustering distribution.
     *
     * @return int|null
     */
    public function get_slot();
    /**
     * Sets the arguments for the command.
     *
     * @param array $arguments List of arguments.
     */
    public function set_arguments(array $arguments);
    /**
     * Sets the raw arguments for the command without processing them.
     *
     * @param array $arguments List of arguments.
     */
    public function set_raw_arguments(array $arguments);
    /**
     * Gets the arguments of the command.
     *
     * @return array
     */
    public function get_arguments();
    /**
     * Gets the argument of the command at the specified index.
     *
     * @param int $index Index of the desired argument.
     *
     * @return mixed|null
     */
    public function get_argument($index);
    /**
     * Parses a raw response and returns a PHP object.
     *
     * @param string|array|null $data Binary string containing the whole response.
     *
     * @return mixed
     */
    public function parse_response($data);
    /**
     * Parses RESP3 protocol response and returns a PHP object.
     *
     * @param  mixed $data
     * @return mixed
     */
    public function parse_resp3response($data);
    /**
     * Returns RESP-formatted representation of command.
     */
    public function serialize_command(): string;
    /**
     * Creates command object from given serialized representation.
     */
    public static function deserialize_command(string $serialized_command): Command_Interface;
}