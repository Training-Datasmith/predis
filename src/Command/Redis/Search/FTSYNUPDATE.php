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
/**
 * @see https://redis.io/commands/ft.synupdate/
 *
 * Update a synonym group
 */
class FTSYNUPDATE extends Redis_Command
{
    public function get_id(): string
    {
        return 'FT.SYNUPDATE';
    }
    public function set_arguments(array $arguments): void
    {
        [$index, $synonym_group_id] = $arguments;
        $command_arguments = [];
        if (!empty($arguments[2])) {
            $command_arguments = $arguments[2]->to_array();
        }
        $terms = array_slice($arguments, 3);
        parent::set_arguments(array_merge([$index, $synonym_group_id], $command_arguments, $terms));
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}