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
trait Aggregate
{
    /**
     * @var string[]
     */
    private static $aggregate_values_enum = ['min' => 'MIN', 'max' => 'MAX', 'sum' => 'SUM'];
    /**
     * @var string
     */
    private static $aggregate_modifier = 'AGGREGATE';
    public function set_arguments(array $arguments): void
    {
        $arguments_length = count($arguments);
        if (static::$aggregate_argument_position_offset >= $arguments_length) {
            parent::set_arguments($arguments);
            return;
        }
        $argument = $arguments[static::$aggregate_argument_position_offset];
        if (is_string($argument) && in_array(strtoupper($argument), self::$aggregate_values_enum)) {
            $argument = self::$aggregate_values_enum[$argument];
        } else {
            $enum_values = implode(', ', array_keys(self::$aggregate_values_enum));
            throw new UnexpectedValueException("Aggregate argument accepts only: {$enum_values} values");
        }
        $arguments_before = array_slice($arguments, 0, static::$aggregate_argument_position_offset);
        $arguments_after = array_slice($arguments, static::$aggregate_argument_position_offset + 1);
        parent::set_arguments(array_merge($arguments_before, [self::$aggregate_modifier], [$argument], $arguments_after));
    }
}