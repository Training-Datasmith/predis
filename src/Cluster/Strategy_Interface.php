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
namespace Predis\Cluster;

use Predis\Cluster\Distributor\Distributor_Interface;
use Predis\Command\Command_Interface;
/**
 * Interface for classes defining the strategy used to calculate an hash out of
 * keys extracted from supported commands.
 *
 * This is mostly useful to support clustering via client-side sharding.
 */
interface Strategy_Interface
{
    /**
     * Returns a slot for the given command used for clustering distribution or
     * NULL when this is not possible.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return int|null
     */
    public function get_slot(Command_Interface $command);
    /**
     * Returns a slot for the given key used for clustering distribution or NULL
     * when this is not possible.
     *
     * @param string $key Key string.
     *
     * @return int|null
     */
    public function get_slot_by_key($key);
    /**
     * Returns a distributor instance to be used by the cluster.
     *
     * @return DistributorInterface
     */
    public function get_distributor();
    /**
     * Checks if the specified array of keys will generate the same hash.
     */
    public function check_same_slot_for_keys(array $keys): bool;
}