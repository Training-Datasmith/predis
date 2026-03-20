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
 * @see https://redis.io/commands/ts.createrule/
 *
 * Create a compaction rule
 */
class TSCREATERULE extends Redis_Command
{
    public function get_id(): string
    {
        return 'TS.CREATERULE';
    }
    public function set_arguments(array $arguments): void
    {
        [$source_key, $dest_key, $aggregator, $bucket_duration] = $arguments;
        $processed_arguments = [$source_key, $dest_key, 'AGGREGATION', $aggregator, $bucket_duration];
        if (count($arguments) === 5) {
            $processed_arguments[] = $arguments[4];
        }
        parent::set_arguments($processed_arguments);
    }
    public function prefix_keys($prefix): void
    {
        if ($arguments = $this->get_arguments()) {
            $arguments[0] = $prefix . $arguments[0];
            $arguments[1] = $prefix . $arguments[1];
            $this->set_raw_arguments($arguments);
        }
    }
}