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
trait DB
{
    private $db_modifier = 'DB';
    public function set_arguments(array $arguments): void
    {
        $arguments_length = count($arguments);
        if (static::$db_argument_position_offset >= $arguments_length) {
            parent::set_arguments($arguments);
            return;
        }
        if (!is_numeric($arguments[static::$db_argument_position_offset])) {
            throw new UnexpectedValueException('DB argument should be a valid numeric value');
        }
        if ($arguments[static::$db_argument_position_offset] < 0) {
            array_splice($arguments, static::$db_argument_position_offset, 1);
            parent::set_arguments($arguments);
            return;
        }
        $argument = $arguments[static::$db_argument_position_offset];
        $arguments_before = array_slice($arguments, 0, static::$db_argument_position_offset);
        $arguments_after = array_slice($arguments, static::$db_argument_position_offset + 1);
        parent::set_arguments(array_merge($arguments_before, [$this->db_modifier], [$argument], $arguments_after));
    }
}