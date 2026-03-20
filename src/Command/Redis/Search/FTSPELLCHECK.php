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
class FTSPELLCHECK extends Redis_Command
{
    public function get_id(): string
    {
        return 'FT.SPELLCHECK';
    }
    public function set_arguments(array $arguments): void
    {
        // If command already deserialized, bypass logic.
        if (in_array('DIALECT', $arguments)) {
            parent::set_arguments($arguments);
            return;
        }
        [$index, $query] = $arguments;
        if (!empty($arguments[2]) && !in_array('DIALECT', $arguments[2]->to_array())) {
            // Default dialect is 2
            $arguments[2]->dialect(2);
        }
        $command_arguments = ['DIALECT', 2];
        if (!empty($arguments[2])) {
            $command_arguments = $arguments[2]->to_array();
        }
        parent::set_arguments(array_merge([$index, $query], $command_arguments));
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}