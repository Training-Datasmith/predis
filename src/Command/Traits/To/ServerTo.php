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
namespace Predis\Command\Traits\To;

use Predis\Command\Argument\Server\To;
trait Server_To
{
    public function set_arguments(array $arguments): void
    {
        $arguments_length = count($arguments);
        if (static::$to_argument_position_offset >= $arguments_length) {
            parent::set_arguments($arguments);
            return;
        }
        /** @var To|null $toArgument */
        $to_argument = $arguments[static::$to_argument_position_offset];
        if (null === $to_argument) {
            array_splice($arguments, static::$to_argument_position_offset, 1, [false]);
            parent::set_arguments($arguments);
            return;
        }
        $arguments_before = array_slice($arguments, 0, static::$to_argument_position_offset);
        $arguments_after = array_slice($arguments, static::$to_argument_position_offset + 1);
        parent::set_arguments(array_merge($arguments_before, $to_argument->to_array(), $arguments_after));
    }
}