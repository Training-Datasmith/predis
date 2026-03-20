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
namespace Predis\Command\Traits\By;

use Predis\Command\Command;
/**
 * @mixin Command
 */
trait By_Argument
{
    private $by_modifier = 'BY';
    public function set_arguments(array $arguments): void
    {
        $arguments_length = count($arguments);
        if (static::$by_argument_position_offset >= $arguments_length || null === $arguments[static::$by_argument_position_offset]) {
            parent::set_arguments($arguments);
            return;
        }
        $argument = $arguments[static::$by_argument_position_offset];
        $arguments_before = array_slice($arguments, 0, static::$by_argument_position_offset);
        $arguments_after = array_slice($arguments, static::$by_argument_position_offset + 1);
        parent::set_arguments(array_merge($arguments_before, [$this->by_modifier, $argument], $arguments_after));
    }
}