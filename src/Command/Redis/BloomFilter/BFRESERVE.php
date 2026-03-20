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
namespace Predis\Command\Redis\Bloom_Filter;

use Predis\Command\Prefixable_Command as RedisCommand;
use Predis\Command\Traits\Bloom_Filters\Expansion;
/**
 * @see https://redis.io/commands/bf.reserve/
 *
 * Creates an empty Bloom Filter with a single sub-filter for the initial capacity
 * requested and with an upper bound error_rate.
 *
 * By default, the filter auto-scales by creating additional sub-filters when capacity is reached.
 * The new sub-filter is created with size of the previous sub-filter multiplied by expansion.
 */
class BFRESERVE extends Redis_Command
{
    use Expansion {
        Expansion::setArguments as setExpansion;
    }
    protected static $expansion_argument_position_offset = 3;
    public function get_id(): string
    {
        return 'BF.RESERVE';
    }
    public function set_arguments(array $arguments): void
    {
        if (array_key_exists(4, $arguments) && $arguments[4]) {
            $arguments[4] = 'NONSCALING';
        }
        $this->set_expansion($arguments);
        $this->filter_arguments();
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}