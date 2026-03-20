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
trait Weights
{
    /**
     * @var string
     */
    private static $weights_modifier = 'WEIGHTS';
    public function set_arguments(array $arguments): void
    {
        $arguments_length = count($arguments);
        if (static::$weights_argument_position_offset >= $arguments_length) {
            parent::set_arguments($arguments);
            return;
        }
        if (!is_array($arguments[static::$weights_argument_position_offset])) {
            throw new UnexpectedValueException('Wrong weights argument type');
        }
        $weights_array = $arguments[static::$weights_argument_position_offset];
        if (empty($weights_array)) {
            unset($arguments[static::$weights_argument_position_offset]);
            parent::set_arguments($arguments);
            return;
        }
        $arguments_before = array_slice($arguments, 0, static::$weights_argument_position_offset);
        $arguments_after = array_slice($arguments, static::$weights_argument_position_offset + 1);
        parent::set_arguments(array_merge($arguments_before, [self::$weights_modifier], $weights_array, $arguments_after));
    }
}