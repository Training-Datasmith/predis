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

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * This is a transitional command. In the next major version this command will replace XREADGROUP.
 */
class XREADGROUP_CLAIM extends Redis_Command
{
    public function get_id(): string
    {
        return 'XREADGROUP';
    }
    public function set_arguments(array $arguments): void
    {
        $processed_arguments = ['GROUP', $arguments[0], $arguments[1]];
        if (count($arguments) >= 4 && null !== $arguments[3]) {
            array_push($processed_arguments, 'COUNT', $arguments[3]);
        }
        if (count($arguments) >= 5 && null !== $arguments[4]) {
            array_push($processed_arguments, 'BLOCK', $arguments[4]);
        }
        if (count($arguments) >= 6 && false !== $arguments[5]) {
            $processed_arguments[] = 'NOACK';
        }
        if (count($arguments) >= 7 && false !== $arguments[6]) {
            array_push($processed_arguments, 'CLAIM', $arguments[6]);
        }
        array_push($processed_arguments, 'STREAMS', ...array_keys($arguments[2]), ...array_values($arguments[2]));
        parent::set_arguments($processed_arguments);
    }
    public function parse_response($data)
    {
        if (!is_array($data) || $data === array_values($data)) {
            return $data;
        }
        // Relay
        $result = [];
        foreach ($data as $key => $value) {
            $group = [$key, $value];
            $result[] = $group;
        }
        return $result;
    }
    public function prefix_keys($prefix): void
    {
        $arguments = $this->get_arguments();
        $key_ids_starting_index = array_search('STREAMS', $arguments) + 1;
        $keys_and_ids_count = count($arguments) - $key_ids_starting_index;
        $keys_count = $keys_and_ids_count / 2;
        for ($i = $key_ids_starting_index; $i < $key_ids_starting_index + $keys_count; $i++) {
            $arguments[$i] = $prefix . $arguments[$i];
        }
        parent::set_raw_arguments($arguments);
    }
}