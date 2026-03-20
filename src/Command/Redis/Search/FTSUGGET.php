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

use Predis\Command\Command as RedisCommand;
/**
 * @see https://redis.io/commands/ft.sugget/
 *
 * Get completion suggestions for a prefix.
 */
class FTSUGGET extends Redis_Command
{
    public function get_id(): string
    {
        return 'FT.SUGGET';
    }
    public function set_arguments(array $arguments): void
    {
        [$key, $prefix] = $arguments;
        $command_arguments = !empty($arguments[2]) ? $arguments[2]->to_array() : [];
        parent::set_arguments(array_merge([$key, $prefix], $command_arguments));
    }
}