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
use Predis\Command\Redis\Utils\Command_Utility;
/**
 * @see http://redis.io/commands/xclaim
 */
class XCLAIM extends Redis_Command
{
    public function get_id(): string
    {
        return 'XCLAIM';
    }
    public function set_arguments(array $arguments): void
    {
        if (count($arguments) < 5) {
            return;
        }
        $processed_arguments = array_slice($arguments, 0, 4);
        $ids = $arguments[4];
        $processed_arguments = array_merge($processed_arguments, is_array($ids) ? $ids : [$ids]);
        if (array_key_exists(5, $arguments) && null !== $arguments[5]) {
            array_push($processed_arguments, 'IDLE', $arguments[5]);
        }
        if (array_key_exists(6, $arguments) && null !== $arguments[6]) {
            array_push($processed_arguments, 'TIME', $arguments[6]);
        }
        if (array_key_exists(7, $arguments) && null !== $arguments[7]) {
            array_push($processed_arguments, 'RETRYCOUNT', $arguments[7]);
        }
        if (array_key_exists(8, $arguments) && false !== $arguments[8]) {
            $processed_arguments[] = 'FORCE';
        }
        if (array_key_exists(9, $arguments) && false !== $arguments[9]) {
            $processed_arguments[] = 'JUSTID';
        }
        if (array_key_exists(10, $arguments) && false !== $arguments[10]) {
            array_push($processed_arguments, 'LASTID', $arguments[10]);
        }
        parent::set_arguments($processed_arguments);
    }
    public function parse_response($data): array
    {
        // JUSTID format
        if (isset($data[0]) && !is_array($data[0])) {
            return $data;
        }
        $result = [];
        foreach ($data as [$id, $kv_dict]) {
            $result[$id] = Command_Utility::array_to_dictionary($kv_dict);
        }
        return $result;
    }
    public function parse_resp3response($data): array
    {
        return $this->parse_response($data);
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}