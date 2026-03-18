<?php

declare(strict_types=1);

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\Utils;

class VectorUtility
{
    /**
     * Converts array of floating numbers into a blob representation.
     *
     * @param  string $format Format string
     */
    public static function toBlob(array $vector, string $format = 'f*'): string
    {
        return pack($format, ...$vector);
    }

    /**
     * Converts blob string vector into array of floatings.
     */
    public static function toArray(string $vector, string $format = 'f*'): array
    {
        return unpack($format, $vector);
    }
}
