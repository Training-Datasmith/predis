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
namespace Predis\Command\Traits\Limit;

use Predis\Command\Argument\Server\Limit_Interface;
trait Limit_Object
{
    public function set_arguments(array $arguments): void
    {
        $argument_position_offset = $this->get_limit_argument_position_offset($arguments);
        if (null === $argument_position_offset) {
            parent::set_arguments($arguments);
            return;
        }
        $limit_object = $arguments[$argument_position_offset];
        $arguments_before = array_slice($arguments, 0, $argument_position_offset);
        $arguments_after = array_slice($arguments, $argument_position_offset + 1);
        parent::set_arguments(array_merge($arguments_before, $limit_object->to_array(), $arguments_after));
    }
    private function get_limit_argument_position_offset(array $arguments): ?int
    {
        foreach ($arguments as $i => $value) {
            if ($value instanceof Limit_Interface) {
                return $i;
            }
        }
        return null;
    }
}