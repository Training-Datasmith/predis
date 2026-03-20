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
namespace Predis\Connection\Replication;

use Predis\Connection\Aggregate_Connection_Interface;
use Predis\Connection\Node_Connection_Interface;
/**
 * Defines a group of Redis nodes in a master / slave replication setup.
 */
interface Replication_Interface extends Aggregate_Connection_Interface
{
    /**
     * Switches the internal connection in use to the master server.
     */
    public function switch_to_master();
    /**
     * Switches the internal connection in use to a random slave server.
     */
    public function switch_to_slave();
    /**
     * Returns the connection in use by the replication backend.
     *
     * @return NodeConnectionInterface
     */
    public function get_current();
    /**
     * Returns the connection to the master server.
     *
     * @return NodeConnectionInterface
     */
    public function get_master();
    /**
     * Returns a list of connections to slave servers.
     *
     * @return NodeConnectionInterface[]
     */
    public function get_slaves();
}