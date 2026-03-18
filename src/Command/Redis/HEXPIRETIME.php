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

class HEXPIRETIME extends RedisCommand
{
    public function getId(): string
    {
        return 'HEXPIRETIME';
    }

    public function setArguments(array $arguments): void
    {
        $processedArguments = [$arguments[0], 'FIELDS', count($arguments[1])];
        $processedArguments = array_merge($processedArguments, $arguments[1]);

        parent::setArguments($processedArguments);
    }

    public function prefixKeys($prefix): void
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
