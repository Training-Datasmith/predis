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
namespace Predis\Command\Traits\Json;

use UnexpectedValueException;
trait Space
{
    private static $space_modifier = 'SPACE';
    public function set_arguments(array $arguments): void
    {
        $arguments_length = count($arguments);
        if (static::$space_argument_position_offset >= $arguments_length) {
            parent::set_arguments($arguments);
            return;
        }
        if ($arguments[static::$space_argument_position_offset] === '') {
            array_splice($arguments, static::$space_argument_position_offset, 1, [false]);
            parent::set_arguments($arguments);
            return;
        }
        $argument = $arguments[static::$space_argument_position_offset];
        if (!is_string($argument)) {
            throw new UnexpectedValueException('Space argument value should be a string');
        }
        $arguments_before = array_slice($arguments, 0, static::$space_argument_position_offset);
        $arguments_after = array_slice($arguments, static::$space_argument_position_offset + 1);
        parent::set_arguments(array_merge($arguments_before, [self::$space_modifier], [$argument], $arguments_after));
    }
}