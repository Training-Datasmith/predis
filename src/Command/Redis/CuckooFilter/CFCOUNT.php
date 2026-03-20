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
namespace Predis\Command\Redis\Cuckoo_Filter;

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see https://redis.io/commands/cf.count/
 *
 * Returns the number of times an item may be in the filter.
 * Because this is a probabilistic data structure, this may not necessarily be accurate.
 */
class CFCOUNT extends Redis_Command
{
    public function get_id(): string
    {
        return 'CF.COUNT';
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}