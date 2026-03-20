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
namespace Predis\Command\Traits;

use UnexpectedValueException;
trait Sorting
{
    private static $sorting_enum = ['asc' => 'ASC', 'desc' => 'DESC'];
    public function set_arguments(array $arguments): void
    {
        $arguments_length = count($arguments);
        if (static::$sort_argument_position_offset >= $arguments_length) {
            parent::set_arguments($arguments);
            return;
        }
        $argument = $arguments[static::$sort_argument_position_offset];
        if (null === $argument) {
            array_splice($arguments, static::$sort_argument_position_offset, 1, [false]);
            parent::set_arguments($arguments);
            return;
        }
        if (!in_array(strtoupper($argument), self::$sorting_enum, true)) {
            $enum_values = implode(', ', array_keys(self::$sorting_enum));
            throw new UnexpectedValueException("Sorting argument accepts only: {$enum_values} values");
        }
        $arguments_before = array_slice($arguments, 0, static::$sort_argument_position_offset);
        $arguments_after = array_slice($arguments, static::$sort_argument_position_offset + 1);
        parent::set_arguments(array_merge($arguments_before, [self::$sorting_enum[$argument]], $arguments_after));
    }
}