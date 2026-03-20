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
namespace Predis\Command\Traits\Expire;

trait Expire_Options
{
    private static $argument_enum = ['nx' => 'NX', 'xx' => 'XX', 'gt' => 'GT', 'lt' => 'LT'];
    public function set_arguments(array $arguments): void
    {
        $value = array_pop($arguments);
        if (null === $value) {
            parent::set_arguments($arguments);
            return;
        }
        if (in_array(strtoupper($value), self::$argument_enum, true)) {
            $arguments[] = self::$argument_enum[strtolower($value)];
        } else {
            $arguments[] = $value;
        }
        parent::set_arguments($arguments);
    }
}