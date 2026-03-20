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
 * @see https://redis.io/commands/ts.incrby/
 *
 * Increase the value of the sample with the maximum existing timestamp,
 * or create a new sample with a value equal to the value of the sample
 * with the maximum existing timestamp with a given increment
 */
class TSINCRBY extends Redis_Command
{
    public function get_id(): string
    {
        return 'TS.INCRBY';
    }
    public function set_arguments(array $arguments): void
    {
        [$key, $value] = $arguments;
        $command_arguments = !empty($arguments[2]) ? $arguments[2]->to_array() : [];
        parent::set_arguments(array_merge([$key, $value], $command_arguments));
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}