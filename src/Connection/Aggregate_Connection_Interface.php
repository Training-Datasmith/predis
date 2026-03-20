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
 * Defines a virtual connection composed of multiple connection instances to
 * single Redis nodes.
 */
interface Aggregate_Connection_Interface extends Connection_Interface
{
    /**
     * Adds a connection instance to the aggregate connection.
     *
     * @param NodeConnectionInterface $connection Connection instance.
     */
    public function add(Node_Connection_Interface $connection);
    /**
     * Removes the specified connection instance from the aggregate connection.
     *
     * @param NodeConnectionInterface $connection Connection instance.
     *
     * @return bool Returns true if the connection was in the pool.
     */
    public function remove(Node_Connection_Interface $connection);
    /**
     * Returns the connection instance in charge for the given command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return NodeConnectionInterface
     */
    public function get_connection_by_command(Command_Interface $command);
    /**
     * Returns a connection instance from the aggregate connection by its alias.
     *
     * @param string $connectionID Connection alias.
     *
     * @return NodeConnectionInterface|null
     */
    public function get_connection_by_id($connection_id);
}