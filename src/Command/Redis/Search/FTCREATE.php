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

use Predis\Command\Argument\Search\Schema_Fields\Field_Interface;
use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see https://redis.io/commands/ft.create/
 *
 * Create an index with the given specification
 */
class FTCREATE extends Redis_Command
{
    public function get_id(): string
    {
        return 'FT.CREATE';
    }
    public function set_arguments(array $arguments): void
    {
        [$index, $schema] = $arguments;
        $command_arguments = !empty($arguments[2]) ? $arguments[2]->to_array() : [];
        $schema = array_reduce($schema, static function (array $carry, Field_Interface $field): array {
            return array_merge($carry, $field->to_array());
        }, []);
        array_unshift($schema, 'SCHEMA');
        parent::set_arguments(array_merge([$index], $command_arguments, $schema));
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}