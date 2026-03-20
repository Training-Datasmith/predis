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
namespace Predis\Command\Argument\Geospatial;

use UnexpectedValueException;
abstract class Abstract_By implements By_Interface
{
    /**
     * @var string[]
     */
    private static $unit_enum = ['m', 'km', 'ft', 'mi'];
    /**
     * @var string
     */
    protected $unit;
    /**
     * {@inheritDoc}
     */
    abstract public function to_array(): array;
    protected function set_unit(string $unit): void
    {
        if (!in_array($unit, self::$unit_enum, true)) {
            throw new UnexpectedValueException('Wrong value given for unit');
        }
        $this->unit = $unit;
    }
}