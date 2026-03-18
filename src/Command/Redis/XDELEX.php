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
 * @see http://redis.io/commands/xdelex
 */
class XDELEX extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return 'XDELEX';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments): void
    {
        $processedArguments = [$arguments[0], strtoupper($arguments[1])];

        array_push($processedArguments, 'IDS', strval(count($arguments[2])), ...$arguments[2]);

        parent::setArguments($processedArguments);
    }

    public function prefixKeys($prefix): void
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
