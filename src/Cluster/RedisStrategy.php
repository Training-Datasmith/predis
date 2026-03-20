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

use Predis\Cluster\Hash\CRC16;
use Predis\Cluster\Hash\Hash_Generator_Interface;
use Predis\Not_Supported_Exception;
/**
 * Default class used by Predis to calculate hashes out of keys of
 * commands supported by redis-cluster.
 */
class Redis_Strategy extends Cluster_Strategy
{
    protected $hash_generator;
    /**
     * @param HashGeneratorInterface|null $hashGenerator Hash generator instance.
     */
    public function __construct(?Hash_Generator_Interface $hash_generator = null)
    {
        parent::__construct();
        $this->hash_generator = $hash_generator ?: new CRC16();
    }
    /**
     * {@inheritdoc}
     */
    public function get_slot_by_key($key): int
    {
        $key = $this->extract_key_tag($key);
        return $this->hash_generator->hash($key) & 0x3fff;
    }
    /**
     * {@inheritdoc}
     */
    public function get_distributor(): void
    {
        $class = get_class($this);
        throw new Not_Supported_Exception("{$class} does not provide an external distributor");
    }
}