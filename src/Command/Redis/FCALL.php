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
use Predis\Command\Traits\Keys;
/**
 * @see https://redis.io/commands/fcall/
 *
 * Invoke a function.
 */
class FCALL extends Redis_Command
{
    use Keys;
    protected static $keys_argument_position_offset = 1;
    public function get_id(): string
    {
        return 'FCALL';
    }
    public function prefix_keys($prefix): void
    {
        $arguments = $this->get_arguments();
        if (isset($arguments[1])) {
            $numkeys = $arguments[1];
            for ($i = 2; $i < $numkeys + 2; $i++) {
                $arguments[$i] = $prefix . $arguments[$i];
            }
        }
        $this->set_raw_arguments($arguments);
    }
}