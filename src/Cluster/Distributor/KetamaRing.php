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

/**
 * This class implements an hashring-based distributor that uses the same
 * algorithm of libketama to distribute keys in a cluster using client-side
 * sharding.
 * @author Lorenzo Castelli <lcastelli@gmail.com>
 */
class Ketama_Ring extends Hash_Ring
{
    public const DEFAULT_REPLICAS = 160;
    /**
     * @param mixed $nodeHashCallback Callback returning a string used to calculate the hash of nodes.
     */
    public function __construct($node_hash_callback = null)
    {
        parent::__construct($this::DEFAULT_REPLICAS, $node_hash_callback);
    }
    /**
     * {@inheritdoc}
     */
    protected function add_node_to_ring(&$ring, $node, $total_nodes, $replicas, $weight_ratio)
    {
        $node_object = $node['object'];
        $node_hash = $this->get_node_hash($node_object);
        $replicas = (int) floor($weight_ratio * $total_nodes * ($replicas / 4));
        for ($i = 0; $i < $replicas; ++$i) {
            $unpacked_digest = unpack('V4', md5("{$node_hash}-{$i}", true));
            foreach ($unpacked_digest as $key) {
                $ring[$key] = $node_object;
            }
        }
    }
    /**
     * {@inheritdoc}
     */
    public function hash($value)
    {
        $hash = unpack('V', md5($value, true));
        return $hash[1];
    }
    /**
     * {@inheritdoc}
     */
    protected function wrap_around_strategy($upper, $lower, $ring_keys_count)
    {
        // Binary search for the first item in ringkeys with a value greater
        // or equal to the key. If no such item exists, return the first item.
        return $lower < $ring_keys_count ? $lower : 0;
    }
}