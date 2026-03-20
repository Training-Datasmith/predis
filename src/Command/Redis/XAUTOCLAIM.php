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
class XAUTOCLAIM extends Redis_Command
{
    public function get_id(): string
    {
        return 'XAUTOCLAIM';
    }
    public function set_arguments(array $arguments): void
    {
        $processed_arguments = array_splice($arguments, 0, 5);
        if (empty($arguments)) {
            parent::set_arguments($processed_arguments);
            return;
        }
        if ($arguments[0] !== null) {
            array_push($processed_arguments, 'COUNT', $arguments[0]);
        }
        if (count($arguments) >= 2 && true === $arguments[1]) {
            $processed_arguments[] = 'JUSTID';
        }
        parent::set_arguments($processed_arguments);
    }
}