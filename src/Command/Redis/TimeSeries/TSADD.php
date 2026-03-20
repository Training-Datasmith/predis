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
namespace Predis\Command\Redis\Time_Series;

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see https://redis.io/commands/ts.add/
 *
 * Append a sample to a time series.
 */
class TSADD extends Redis_Command
{
    public function get_id(): string
    {
        return 'TS.ADD';
    }
    public function set_arguments(array $arguments): void
    {
        [$key, $timestamp, $value] = $arguments;
        $command_arguments = !empty($arguments[3]) ? $arguments[3]->to_array() : [];
        parent::set_arguments(array_merge([$key, $timestamp, $value], $command_arguments));
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}