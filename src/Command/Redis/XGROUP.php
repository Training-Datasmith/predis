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
 * @see https://redis.io/commands/?name=xgroup
 *
 * Container command corresponds to any XGROUP *.
 * Represents any XGROUP command with subcommand as first argument.
 */
class XGROUP extends RedisCommand
{
    public function getId(): string
    {
        return 'XGROUP';
    }

    public function setArguments(array $arguments): void
    {
        switch ($arguments[0]) {
            case 'CREATE':
                $this->setCreateArguments($arguments);

                return;

            case 'SETID':
                $this->setSetIdArguments($arguments);

                return;

            default:
                parent::setArguments($arguments);
        }
    }

    private function setCreateArguments(array $arguments): void
    {
        $processedArguments = [$arguments[0], $arguments[1], $arguments[2], $arguments[3]];

        if (array_key_exists(4, $arguments) && true === $arguments[4]) {
            $processedArguments[] = 'MKSTREAM';
        }

        if (array_key_exists(5, $arguments)) {
            array_push($processedArguments, 'ENTRIESREAD', $arguments[5]);
        }

        parent::setArguments($processedArguments);
    }

    private function setSetIdArguments(array $arguments): void
    {
        $processedArguments = [$arguments[0], $arguments[1], $arguments[2], $arguments[3]];

        if (array_key_exists(4, $arguments)) {
            array_push($processedArguments, 'ENTRIESREAD', $arguments[4]);
        }

        parent::setArguments($processedArguments);
    }

    public function prefixKeys($prefix): void
    {
        $arguments = $this->getArguments();
        $arguments[1] = $prefix . $arguments[1];
        $this->setRawArguments($arguments);
    }
}
