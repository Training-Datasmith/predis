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
namespace Predis\Cluster\Distributor;

use Predis\Cluster\Hash\Hash_Generator_Interface;
/**
 * A distributor implements the logic to automatically distribute keys among
 * several nodes for client-side sharding.
 */
interface Distributor_Interface
{
    /**
     * Adds a node to the distributor with an optional weight.
     *
     * @param mixed $node   Node object.
     * @param int   $weight Weight for the node.
     */
    public function add($node, $weight = null);
    /**
     * Removes a node from the distributor.
     *
     * @param mixed $node Node object.
     */
    public function remove($node);
    /**
     * Returns the corresponding slot of a node from the distributor using the
     * computed hash of a key.
     *
     * @param mixed $hash
     *
     * @return mixed
     */
    public function get_slot($hash);
    /**
     * Returns a node from the distributor using its assigned slot ID.
     *
     * @param mixed $slot
     *
     * @return mixed|null
     */
    public function get_by_slot($slot);
    /**
     * Returns a node from the distributor using the computed hash of a key.
     *
     * @param mixed $hash
     *
     * @return mixed
     */
    public function get_by_hash($hash);
    /**
     * Returns a node from the distributor mapping to the specified value.
     *
     * @param string $value
     *
     * @return mixed
     */
    public function get($value);
    /**
     * Returns the underlying hash generator instance.
     *
     * @return HashGeneratorInterface
     */
    public function get_hash_generator();
}