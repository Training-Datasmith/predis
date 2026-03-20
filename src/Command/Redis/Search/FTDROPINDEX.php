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
namespace Predis\Command\Redis\Search;

use Predis\Command\Prefixable_Command as RedisCommand;
class FTDROPINDEX extends Redis_Command
{
    public function get_id(): string
    {
        return 'FT.DROPINDEX';
    }
    public function set_arguments(array $arguments): void
    {
        [$index] = $arguments;
        $command_arguments = [];
        if (!empty($arguments[1])) {
            $command_arguments = $arguments[1]->to_array();
        }
        parent::set_arguments(array_merge([$index], $command_arguments));
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}