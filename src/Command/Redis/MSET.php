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
namespace Predis\Command\Redis;

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see http://redis.io/commands/mset
 */
class MSET extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'MSET';
    }
    /**
     * {@inheritdoc}
     */
    public function set_arguments(array $arguments): void
    {
        if (count($arguments) === 1 && is_array($arguments[0])) {
            $flattened_k_vs = [];
            $args = $arguments[0];
            foreach ($args as $k => $v) {
                $flattened_k_vs[] = $k;
                $flattened_k_vs[] = $v;
            }
            $arguments = $flattened_k_vs;
        }
        parent::set_arguments($arguments);
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_interleaved_argument($prefix);
    }
}