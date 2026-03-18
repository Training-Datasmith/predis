<?php

declare(strict_types=1);

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\BloomFilter;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/bf.add/
 *
 * Creates an empty Bloom Filter with a single sub-filter for the
 * initial capacity requested and with an upper bound error_rate.
 */
class BFADD extends RedisCommand
{
    public function getId(): string
    {
        return 'BF.ADD';
    }

    public function prefixKeys($prefix): void
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
