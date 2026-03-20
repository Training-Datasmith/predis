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
namespace Predis\Command\Redis\Top_K;

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see https://redis.io/commands/topk.incrby/
 *
 * Increase the score of an item in the data structure by increment.
 * Multiple items' score can be increased at once.
 * If an item enters the Top-K list, the item which is expelled is returned.
 */
class TOPKINCRBY extends Redis_Command
{
    public function get_id(): string
    {
        return 'TOPK.INCRBY';
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}