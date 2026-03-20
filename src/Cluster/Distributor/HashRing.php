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
 * This class implements an hashring-based distributor that uses the same
 * algorithm of memcached to distribute keys in a cluster using client-side
 * sharding.
 * @author Lorenzo Castelli <lcastelli@gmail.com>
 */
class Hash_Ring implements Distributor_Interface, Hash_Generator_Interface
{
    public const DEFAULT_REPLICAS = 128;
    public const DEFAULT_WEIGHT = 100;
    private $ring;
    private $ring_keys;
    private $ring_keys_count;
    private $replicas;
    private $node_hash_callback;
    private $nodes = [];
    /**
     * @param int   $replicas         Number of replicas in the ring.
     * @param mixed $nodeHashCallback Callback returning a string used to calculate the hash of nodes.
     */
    public function __construct($replicas = self::DEFAULT_REPLICAS, $node_hash_callback = null)
    {
        $this->replicas = $replicas;
        $this->node_hash_callback = $node_hash_callback;
    }
    /**
     * Adds a node to the ring with an optional weight.
     *
     * @param mixed $node   Node object.
     * @param int   $weight Weight for the node.
     */
    public function add($node, $weight = null): void
    {
        // In case of collisions in the hashes of the nodes, the node added
        // last wins, thus the order in which nodes are added is significant.
        $this->nodes[] = ['object' => $node, 'weight' => (int) $weight ?: $this::DEFAULT_WEIGHT];
        $this->reset();
    }
    /**
     * {@inheritdoc}
     */
    public function remove($node): void
    {
        // A node is removed by resetting the ring so that it's recreated from
        // scratch, in order to reassign possible hashes with collisions to the
        // right node according to the order in which they were added in the
        // first place.
        for ($i = 0; $i < count($this->nodes); ++$i) {
            if ($this->nodes[$i]['object'] === $node) {
                array_splice($this->nodes, $i, 1);
                $this->reset();
                break;
            }
        }
    }
    /**
     * Resets the distributor.
     */
    private function reset(): void
    {
        unset($this->ring, $this->ring_keys, $this->ring_keys_count);
    }
    /**
     * Returns the initialization status of the distributor.
     */
    private function is_initialized(): bool
    {
        return isset($this->ring_keys);
    }
    /**
     * Calculates the total weight of all the nodes in the distributor.
     *
     * @return int
     */
    private function compute_total_weight()
    {
        $total_weight = 0;
        foreach ($this->nodes as $node) {
            $total_weight += $node['weight'];
        }
        return $total_weight;
    }
    /**
     * Initializes the distributor.
     */
    private function initialize(): void
    {
        if ($this->is_initialized()) {
            return;
        }
        if (!$this->nodes) {
            throw new Empty_Ring_Exception('Cannot initialize an empty hashring.');
        }
        $this->ring = [];
        $total_weight = $this->compute_total_weight();
        $nodes_count = count($this->nodes);
        foreach ($this->nodes as $node) {
            $weight_ratio = $node['weight'] / $total_weight;
            $this->add_node_to_ring($this->ring, $node, $nodes_count, $this->replicas, $weight_ratio);
        }
        ksort($this->ring, SORT_NUMERIC);
        $this->ring_keys = array_keys($this->ring);
        $this->ring_keys_count = count($this->ring_keys);
    }
    /**
     * Implements the logic needed to add a node to the hashring.
     *
     * @param array $ring        Source hashring.
     * @param mixed $node        Node object to be added.
     * @param int   $totalNodes  Total number of nodes.
     * @param int   $replicas    Number of replicas in the ring.
     * @param float $weightRatio Weight ratio for the node.
     */
    protected function add_node_to_ring(array &$ring, array $node, $total_nodes, $replicas, $weight_ratio)
    {
        $node_object = $node['object'];
        $node_hash = $this->get_node_hash($node_object);
        $replicas = (int) round($weight_ratio * $total_nodes * $replicas);
        for ($i = 0; $i < $replicas; ++$i) {
            $key = $this->hash("{$node_hash}:{$i}");
            $ring[$key] = $node_object;
        }
    }
    /**
     * {@inheritdoc}
     */
    protected function get_node_hash($node_object)
    {
        if (!isset($this->node_hash_callback)) {
            return (string) $node_object;
        }
        return call_user_func($this->node_hash_callback, $node_object);
    }
    /**
     * {@inheritdoc}
     */
    public function hash($value): int
    {
        return crc32($value);
    }
    /**
     * {@inheritdoc}
     */
    public function get_by_hash($hash)
    {
        return $this->ring[$this->get_slot($hash)];
    }
    /**
     * {@inheritdoc}
     */
    public function get_by_slot($slot)
    {
        $this->initialize();
        if (isset($this->ring[$slot])) {
            return $this->ring[$slot];
        }
    }
    /**
     * {@inheritdoc}
     */
    public function get_slot($hash)
    {
        $this->initialize();
        $ring_keys = $this->ring_keys;
        $upper = $this->ring_keys_count - 1;
        $lower = 0;
        while ($lower <= $upper) {
            $index = $lower + $upper >> 1;
            $item = $ring_keys[$index];
            if ($item > $hash) {
                $upper = $index - 1;
            } elseif ($item < $hash) {
                $lower = $index + 1;
            } else {
                return $item;
            }
        }
        return $ring_keys[$this->wrap_around_strategy($upper, $lower, $this->ring_keys_count)];
    }
    /**
     * {@inheritdoc}
     */
    public function get($value)
    {
        $hash = $this->hash($value);
        return $this->get_by_hash($hash);
    }
    /**
     * Implements a strategy to deal with wrap-around errors during binary searches.
     *
     * @param int $upper
     * @param int $lower
     * @param int $ringKeysCount
     *
     * @return int
     */
    protected function wrap_around_strategy($upper, $lower, $ring_keys_count)
    {
        // Binary search for the last item in ringkeys with a value less or
        // equal to the key. If no such item exists, return the last item.
        return $upper >= 0 ? $upper : $ring_keys_count - 1;
    }
    /**
     * {@inheritdoc}
     */
    public function get_hash_generator(): self
    {
        return $this;
    }
}