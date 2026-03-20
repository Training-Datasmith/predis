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
namespace Predis\Connection\Cluster;

use Predis\Cluster\Strategy_Interface;
use Predis\Command\Command_Interface;
use Predis\Connection\Aggregate_Connection_Interface;
/**
 * Defines a cluster of Redis servers formed by aggregating multiple connection
 * instances to single Redis nodes.
 */
interface Cluster_Interface extends Aggregate_Connection_Interface
{
    /**
     * Executes given command on each connection from connection pool.
     */
    public function execute_command_on_each_node(Command_Interface $command): array;
    /**
     * Returns the underlying command hash strategy used to hash commands by
     * using keys found in their arguments.
     */
    public function get_cluster_strategy(): Strategy_Interface;
}