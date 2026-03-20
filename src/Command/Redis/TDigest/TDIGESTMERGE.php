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
namespace Predis\Command\Redis\T_Digest;

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see https://redis.io/commands/tdigest.merge/
 *
 * Merges multiple t-digest sketches into a single sketch.
 */
class TDIGESTMERGE extends Redis_Command
{
    public function get_id(): string
    {
        return 'TDIGEST.MERGE';
    }
    public function set_arguments(array $arguments): void
    {
        $processed_arguments = array_merge([$arguments[0], count($arguments[1])], $arguments[1]);
        for ($i = 2, $i_max = count($arguments); $i < $i_max; $i++) {
            if (is_int($arguments[$i]) && $arguments[$i] !== 0) {
                array_push($processed_arguments, 'COMPRESSION', $arguments[$i]);
            } elseif (is_bool($arguments[$i]) && $arguments[$i]) {
                $processed_arguments[] = 'OVERRIDE';
            }
        }
        parent::set_arguments($processed_arguments);
    }
    public function prefix_keys($prefix): void
    {
        if ($arguments = $this->get_arguments()) {
            $arguments[0] = $prefix . $arguments[0];
            for ($i = 2, $i_max = (int) $arguments[1] + 2; $i < $i_max; $i++) {
                $arguments[$i] = $prefix . $arguments[$i];
            }
            $this->set_raw_arguments($arguments);
        }
    }
}