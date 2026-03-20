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
trait Storedist
{
    public function set_arguments(array $arguments): void
    {
        $arguments_length = count($arguments);
        if (static::$store_dist_argument_position_offset >= $arguments_length || false === $arguments[static::$store_dist_argument_position_offset]) {
            parent::set_arguments($arguments);
            return;
        }
        $argument = $arguments[static::$store_dist_argument_position_offset];
        if (true === $argument) {
            $argument = 'STOREDIST';
        } else {
            throw new UnexpectedValueException('Wrong STOREDIST argument type');
        }
        $arguments_before = array_slice($arguments, 0, static::$store_dist_argument_position_offset);
        $arguments_after = array_slice($arguments, static::$store_dist_argument_position_offset + 1);
        parent::set_arguments(array_merge($arguments_before, [$argument], $arguments_after));
    }
}