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
class BITFIELD_RO extends Redis_Command
{
    public function get_id(): string
    {
        return 'BITFIELD_RO';
    }
    public function set_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0]];
        if (array_key_exists(1, $arguments) && is_array($arguments[1])) {
            // Convert encoding => offset, into GET, encoding, offset
            array_walk($arguments[1], static function ($value, $key) use (&$processed_arguments): void {
                array_push($processed_arguments, 'GET', $key, $value);
            });
        }
        parent::set_arguments($processed_arguments);
    }
}