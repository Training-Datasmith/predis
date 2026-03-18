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
 * @see http://redis.io/commands/xackdel
 */
class XACKDEL extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return 'XACKDEL';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments): void
    {
        $processedArguments = [$arguments[0], $arguments[1], strtoupper($arguments[2])];

        array_push($processedArguments, 'IDS', strval(count($arguments[3])), ...$arguments[3]);

        parent::setArguments($processedArguments);
    }

    public function prefixKeys($prefix): void
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
