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
 * @deprecated Public API will be changed in the next major version.
 * XREADGROUP_CLAIM API will be used instead.
 */
class XREADGROUP extends Redis_Command
{
    public function get_id(): string
    {
        return 'XREADGROUP';
    }
    public function set_arguments(array $arguments): void
    {
        $processed_arguments = ['GROUP', $arguments[0], $arguments[1]];
        if (count($arguments) >= 3 && null !== $arguments[2]) {
            array_push($processed_arguments, 'COUNT', $arguments[2]);
        }
        if (count($arguments) >= 4 && null !== $arguments[3]) {
            array_push($processed_arguments, 'BLOCK', $arguments[3]);
        }
        if (count($arguments) >= 5 && false !== $arguments[4]) {
            $processed_arguments[] = 'NOACK';
        }
        $processed_arguments[] = 'STREAMS';
        $key_or_ids = array_slice($arguments, 5);
        parent::set_arguments(array_merge($processed_arguments, $key_or_ids));
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