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
namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
/**
 * @see https://redis.io/commands/lcs/
 *
 * The LCS command implements the longest common subsequence algorithm.
 */
class LCS extends Redis_Command
{
    public function get_id(): string
    {
        return 'LCS';
    }
    public function set_arguments(array $arguments): void
    {
        if (isset($arguments[2]) && $arguments[2]) {
            $arguments[2] = 'LEN';
        }
        if (isset($arguments[3]) && $arguments[3]) {
            $arguments[3] = 'IDX';
        }
        if (isset($arguments[5]) && $arguments[5]) {
            $arguments[5] = 'WITHMATCHLEN';
        }
        if (isset($arguments[4])) {
            if ($arguments[4] !== 0) {
                $arguments_before = array_slice($arguments, 0, 4);
                $arguments_after = array_slice($arguments, 5);
                $arguments = array_merge($arguments_before, ['MINMATCHLEN', $arguments[4]], $arguments_after);
            } else {
                $arguments[4] = false;
            }
        }
        parent::set_arguments($arguments);
        $this->filter_arguments();
    }
    public function parse_response($data)
    {
        if (is_array($data)) {
            if ($data !== array_values($data)) {
                return $data;
                // Relay
            }
            return [$data[0] => $data[1], $data[2] => $data[3]];
        }
        return $data;
    }
}