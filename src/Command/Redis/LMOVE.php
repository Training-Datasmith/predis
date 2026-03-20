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
class LMOVE extends Redis_Command
{
    public function get_id(): string
    {
        return 'LMOVE';
    }
    public function prefix_keys($prefix): void
    {
        if ($arguments = $this->get_arguments()) {
            $arguments[0] = $prefix . $arguments[0];
            $arguments[1] = $prefix . $arguments[1];
            $this->set_raw_arguments($arguments);
        }
    }
}