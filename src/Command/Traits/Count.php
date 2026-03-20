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
trait Count
{
    private $count_modifier = 'COUNT';
    private $any_modifier = 'ANY';
    public function set_arguments(array $arguments, bool $any = false): void
    {
        $arguments_length = count($arguments);
        if (static::$count_argument_position_offset >= $arguments_length) {
            parent::set_arguments($arguments);
            return;
        }
        if ($arguments[static::$count_argument_position_offset] === -1) {
            array_splice($arguments, static::$count_argument_position_offset, 1, [false]);
            parent::set_arguments($arguments);
            return;
        }
        if ($arguments[static::$count_argument_position_offset] < 1) {
            throw new UnexpectedValueException('Wrong count argument value or position offset');
        }
        $count_argument = $arguments[static::$count_argument_position_offset];
        $arguments_before = array_slice($arguments, 0, static::$count_argument_position_offset);
        $arguments_after = array_slice($arguments, static::$count_argument_position_offset + 2);
        if (!$any) {
            $arguments_after = array_slice($arguments, static::$count_argument_position_offset + 1);
            parent::set_arguments(array_merge($arguments_before, [$this->count_modifier], [$count_argument], $arguments_after));
            return;
        }
        parent::set_arguments(array_merge($arguments_before, [$this->count_modifier], [$count_argument], [$this->any_modifier], $arguments_after));
    }
}