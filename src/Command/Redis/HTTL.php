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
class HTTL extends Redis_Command
{
    public function get_id(): string
    {
        return 'HTTL';
    }
    public function set_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0], 'FIELDS', count($arguments[1])];
        $processed_arguments = array_merge($processed_arguments, $arguments[1]);
        parent::set_arguments($processed_arguments);
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}