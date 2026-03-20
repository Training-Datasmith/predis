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
namespace Predis\Command\Traits\Get;

use UnexpectedValueException;
trait Get
{
    private static $get_modifier = 'GET';
    public function set_arguments(array $arguments): void
    {
        $arguments_length = count($arguments);
        if (static::$get_argument_position_offset >= $arguments_length) {
            parent::set_arguments($arguments);
            return;
        }
        if (!is_array($arguments[static::$get_argument_position_offset])) {
            throw new UnexpectedValueException('Wrong get argument type');
        }
        $patterns = [];
        foreach ($arguments[static::$get_argument_position_offset] as $pattern) {
            $patterns[] = self::$get_modifier;
            $patterns[] = $pattern;
        }
        $arguments_before_keys = array_slice($arguments, 0, static::$get_argument_position_offset);
        $arguments_after_keys = array_slice($arguments, static::$get_argument_position_offset + 1);
        parent::set_arguments(array_merge($arguments_before_keys, $patterns, $arguments_after_keys));
    }
}