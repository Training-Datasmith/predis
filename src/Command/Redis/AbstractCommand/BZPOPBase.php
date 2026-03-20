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
namespace Predis\Command\Redis\Abstract_Command;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Keys;
abstract class Bzpop_Base extends Redis_Command
{
    use Keys {
        Keys::setArguments as setKeys;
    }
    protected static $keys_argument_position_offset = 0;
    abstract public function get_id(): string;
    public function set_arguments(array $arguments): void
    {
        $this->set_keys($arguments, false);
    }
    public function parse_response($data)
    {
        $key = array_shift($data);
        if (null === $key) {
            return [$key];
        }
        return array_combine([$key], [[$data[0] => $data[1]]]);
    }
    /**
     * @param                                       $data
     * @return array|false|mixed|null[]|string|null
     */
    public function parse_resp3response($data)
    {
        return $this->parse_response($data);
    }
}