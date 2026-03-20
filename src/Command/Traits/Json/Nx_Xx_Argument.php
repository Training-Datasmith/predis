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

use Predis\Command\Command;
use UnexpectedValueException;
/**
 * @mixin Command
 */
trait Nx_Xx_Argument
{
    /**
     * @var string[]
     */
    private static $argument_enum = ['nx' => 'NX', 'xx' => 'XX'];
    public function set_arguments(array $arguments): void
    {
        $arguments_length = count($arguments);
        if (static::$nx_xx_argument_position_offset >= $arguments_length) {
            parent::set_arguments($arguments);
            return;
        }
        if (null === $arguments[static::$nx_xx_argument_position_offset]) {
            array_splice($arguments, static::$nx_xx_argument_position_offset, 1, [false]);
            parent::set_arguments($arguments);
            return;
        }
        $argument = $arguments[static::$nx_xx_argument_position_offset];
        if (!in_array(strtoupper($argument), self::$argument_enum, true)) {
            $enum_values = implode(', ', array_keys(self::$argument_enum));
            throw new UnexpectedValueException("Argument accepts only: {$enum_values} values");
        }
        $arguments_before = array_slice($arguments, 0, static::$nx_xx_argument_position_offset);
        $arguments_after = array_slice($arguments, static::$nx_xx_argument_position_offset + 1);
        parent::set_arguments(array_merge($arguments_before, [self::$argument_enum[strtolower($argument)]], $arguments_after));
    }
}