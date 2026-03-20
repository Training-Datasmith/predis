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
class XREAD extends Redis_Command
{
    public function get_id(): string
    {
        return 'XREAD';
    }
    public function set_arguments(array $arguments): void
    {
        $processed_arguments = [];
        if (array_key_exists(0, $arguments) && null !== $arguments[0]) {
            array_push($processed_arguments, 'COUNT', $arguments[0]);
        }
        if (array_key_exists(1, $arguments) && null !== $arguments[1]) {
            array_push($processed_arguments, 'BLOCK', $arguments[1]);
        }
        if (array_key_exists(2, $arguments) && null !== $arguments[2]) {
            $processed_arguments[] = 'STREAMS';
            $processed_arguments = array_merge($processed_arguments, $arguments[2]);
        }
        $ids = array_slice($arguments, 3);
        $processed_arguments = array_merge($processed_arguments, $ids);
        parent::set_arguments($processed_arguments);
    }
    public function parse_response($data)
    {
        if (!$data) {
            return [];
        }
        if ($data !== array_values($data)) {
            return $data;
            // Relay
        }
        $processed_data = [];
        foreach ($data as $stream) {
            $processed_data[$stream[0]] = $stream[1];
        }
        return $processed_data;
    }
}