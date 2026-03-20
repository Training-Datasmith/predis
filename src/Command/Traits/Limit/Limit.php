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
namespace Predis\Command\Traits\Limit;

use Predis\Command\Command;
use UnexpectedValueException;
/**
 * @mixin Command
 */
trait Limit
{
    private static $limit_modifier = 'LIMIT';
    public function set_arguments(array $arguments): void
    {
        $arguments_length = count($arguments);
        $arguments_before = array_slice($arguments, 0, static::$limit_argument_position_offset);
        if (static::$limit_argument_position_offset >= $arguments_length || false === $arguments[static::$limit_argument_position_offset]) {
            parent::set_arguments($arguments_before);
            return;
        }
        $argument = $arguments[static::$limit_argument_position_offset];
        $arguments_after = array_slice($arguments, static::$limit_argument_position_offset + 1);
        if (true === $argument) {
            parent::set_arguments(array_merge($arguments_before, [self::$limit_modifier], $arguments_after));
            return;
        }
        if (!is_int($argument)) {
            throw new UnexpectedValueException('Wrong limit argument type');
        }
        parent::set_arguments(array_merge($arguments_before, [self::$limit_modifier], [$argument], $arguments_after));
    }
}