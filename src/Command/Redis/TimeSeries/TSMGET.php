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

namespace Predis\Command\Redis\TimeSeries;

use Predis\Command\Command as RedisCommand;

class TSMGET extends RedisCommand
{
    public function getId(): string
    {
        return 'TS.MGET';
    }

    public function setArguments(array $arguments): void
    {
        $processedArguments = [];
        $argumentsObject = array_shift($arguments);
        $commandArguments = $argumentsObject->toArray();

        array_push($processedArguments, 'FILTER', ...$arguments);

        parent::setArguments(array_merge(
            $commandArguments,
            $processedArguments
        ));
    }
}
