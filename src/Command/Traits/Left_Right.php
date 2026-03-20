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

use Predis\Command\Command;
use UnexpectedValueException;
/**
 * @mixin Command
 */
trait Left_Right
{
    /**
     * @var array{string: string}
     */
    private static $left_right_enum = ['left' => 'LEFT', 'right' => 'RIGHT'];
    public function set_arguments(array $arguments): void
    {
        $arguments_length = count($arguments);
        if (static::$left_right_argument_position_offset >= $arguments_length) {
            $arguments[] = 'LEFT';
            parent::set_arguments($arguments);
            return;
        }
        $argument = $arguments[static::$left_right_argument_position_offset];
        if (is_string($argument) && in_array(strtoupper($argument), self::$left_right_enum, true)) {
            $argument = self::$left_right_enum[$argument];
        } else {
            $enum_values = implode(', ', array_keys(self::$left_right_enum));
            throw new UnexpectedValueException("Left/Right argument accepts only: {$enum_values} values");
        }
        $arguments_before = array_slice($arguments, 0, static::$left_right_argument_position_offset);
        $arguments_after = array_slice($arguments, static::$left_right_argument_position_offset + 1);
        parent::set_arguments(array_merge($arguments_before, [$argument], $arguments_after));
    }
}