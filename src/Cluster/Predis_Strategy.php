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
use Predis\Cluster\Distributor\Hash_Ring;
/**
 * Default cluster strategy used by Predis to handle client-side sharding.
 */
class Predis_Strategy extends Cluster_Strategy
{
    protected $distributor;
    /**
     * @param DistributorInterface|null $distributor Optional distributor instance.
     */
    public function __construct(?Distributor_Interface $distributor = null)
    {
        parent::__construct();
        $this->distributor = $distributor ?: new Hash_Ring();
    }
    /**
     * {@inheritdoc}
     */
    public function get_slot_by_key($key)
    {
        $key = $this->extract_key_tag($key);
        $hash = $this->distributor->hash($key);
        return $this->distributor->get_slot($hash);
    }
    /**
     * {@inheritdoc}
     */
    public function check_same_slot_for_keys(array $keys): bool
    {
        if (!$count = count($keys)) {
            return false;
        }
        $current_key = $this->extract_key_tag($keys[0]);
        for ($i = 1; $i < $count; ++$i) {
            $next_key = $this->extract_key_tag($keys[$i]);
            if ($current_key !== $next_key) {
                return false;
            }
        }
        return true;
    }
    /**
     * {@inheritdoc}
     */
    public function get_distributor()
    {
        return $this->distributor;
    }
}