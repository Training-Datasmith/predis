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
namespace Predis\Command\Redis\Json;

use Predis\Command\Prefixable_Command as RedisCommand;
class JSONMGET extends Redis_Command
{
    public function get_id(): string
    {
        return 'JSON.MGET';
    }
    public function set_arguments(array $arguments): void
    {
        $unpacked_arguments = [];
        foreach ($arguments[0] as $key) {
            $unpacked_arguments[] = $key;
        }
        $unpacked_arguments[] = $arguments[1];
        parent::set_arguments($unpacked_arguments);
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_skipping_last_argument($prefix);
    }
}