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
class VRANDMEMBER extends Redis_Command
{
    public function get_id(): string
    {
        return 'VRANDMEMBER';
    }
    public function set_arguments(array $arguments): void
    {
        $last_arg = array_pop($arguments);
        if (!is_null($last_arg)) {
            $arguments[] = $last_arg;
        }
        parent::set_arguments($arguments);
    }
}