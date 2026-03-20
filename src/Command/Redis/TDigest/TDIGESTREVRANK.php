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
namespace Predis\Command\Redis\T_Digest;

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see https://redis.io/commands/tdigest.revrank/
 *
 * Returns, for each input value (floating-point), the estimated reverse rank
 * of the value (the number of observations in the sketch that are larger than
 * the value + half the number of observations that are equal to the value).
 */
class TDIGESTREVRANK extends Redis_Command
{
    public function get_id(): string
    {
        return 'TDIGEST.REVRANK';
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}