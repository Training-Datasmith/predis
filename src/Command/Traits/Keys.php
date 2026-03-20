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
trait Keys
{
    public function set_arguments(array $arguments, bool $with_numkeys = true): void
    {
        $arguments_length = count($arguments);
        if (static::$keys_argument_position_offset > $arguments_length || !is_array($arguments[static::$keys_argument_position_offset])) {
            throw new UnexpectedValueException('Wrong keys argument type or position offset');
        }
        $keys_argument = $arguments[static::$keys_argument_position_offset];
        $arguments_before_keys = array_slice($arguments, 0, static::$keys_argument_position_offset);
        $arguments_after_keys = array_slice($arguments, static::$keys_argument_position_offset + 1);
        if ($with_numkeys) {
            $numkeys = count($keys_argument);
            parent::set_arguments(array_merge($arguments_before_keys, [$numkeys], $keys_argument, $arguments_after_keys));
            return;
        }
        parent::set_arguments(array_merge($arguments_before_keys, $keys_argument, $arguments_after_keys));
    }
}