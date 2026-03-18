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

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/spublish/
 *
 * Posts a message to the given shard channel.
 */
class SPUBLISH extends RedisCommand
{
    public function getId(): string
    {
        return 'SPUBLISH';
    }

    public function prefixKeys($prefix): void
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
