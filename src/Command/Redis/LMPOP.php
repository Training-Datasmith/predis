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
use Predis\Command\Traits\Count;
use Predis\Command\Traits\Keys;
use Predis\Command\Traits\Left_Right;
class LMPOP extends Redis_Command
{
    use Keys {
        Keys::setArguments as setKeys;
    }
    use Left_Right {
        Left_Right::setArguments as setLeftRight;
    }
    use Count {
        Count::setArguments as setCount;
    }
    protected static $keys_argument_position_offset = 0;
    protected static $left_right_argument_position_offset = 1;
    protected static $count_argument_position_offset = 2;
    public function get_id(): string
    {
        return 'LMPOP';
    }
    public function set_arguments(array $arguments): void
    {
        $this->set_count($arguments);
        $arguments = $this->get_arguments();
        $this->set_left_right($arguments);
        $arguments = $this->get_arguments();
        $this->set_keys($arguments);
        $this->filter_arguments();
    }
    public function parse_response($data): ?array
    {
        if (null === $data) {
            return null;
        }
        return [$data[0] => $data[1]];
    }
    public function parse_resp3response($data)
    {
        return $this->parse_response($data);
    }
    public function prefix_keys($prefix): void
    {
        $arguments = $this->get_arguments();
        $keys_offset = static::$keys_argument_position_offset;
        $keys_count = $arguments[$keys_offset];
        $keys = array_slice($arguments, $keys_offset + 1, $keys_count);
        $prefixed_keys = array_map(static function (string $key) use ($prefix): string {
            return $prefix . $key;
        }, $keys);
        $arguments_before = array_slice($arguments, 0, $keys_offset + 1);
        $arguments_after = array_slice($arguments, $keys_offset + $keys_count + 1);
        $this->set_raw_arguments(array_merge($arguments_before, $prefixed_keys, $arguments_after));
    }
}