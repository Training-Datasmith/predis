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
namespace Predis\Command\Traits\Bloom_Filters;

use Predis\Command\Command;
use UnexpectedValueException;
/**
 * @mixin Command
 */
trait Bucket_Size
{
    private static $bucket_size_modifier = 'BUCKETSIZE';
    public function set_arguments(array $arguments): void
    {
        $arguments_length = count($arguments);
        if (static::$bucket_size_argument_position_offset >= $arguments_length) {
            parent::set_arguments($arguments);
            return;
        }
        if ($arguments[static::$bucket_size_argument_position_offset] === -1) {
            array_splice($arguments, static::$bucket_size_argument_position_offset, 1, [false]);
            parent::set_arguments($arguments);
            return;
        }
        if ($arguments[static::$bucket_size_argument_position_offset] < 1) {
            throw new UnexpectedValueException('Wrong bucket size argument value or position offset');
        }
        $argument = $arguments[static::$bucket_size_argument_position_offset];
        $arguments_before = array_slice($arguments, 0, static::$bucket_size_argument_position_offset);
        $arguments_after = array_slice($arguments, static::$bucket_size_argument_position_offset + 1);
        parent::set_arguments(array_merge($arguments_before, [self::$bucket_size_modifier], [$argument], $arguments_after));
    }
}