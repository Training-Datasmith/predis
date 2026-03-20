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
 * @see https://redis.io/commands/ts.get/
 *
 * Get the sample with the highest timestamp from a given time series.
 */
class TSGET extends Redis_Command
{
    public function get_id(): string
    {
        return 'TS.GET';
    }
    public function set_arguments(array $arguments): void
    {
        [$key] = $arguments;
        $command_arguments = !empty($arguments[1]) ? $arguments[1]->to_array() : [];
        parent::set_arguments(array_merge([$key], $command_arguments));
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}