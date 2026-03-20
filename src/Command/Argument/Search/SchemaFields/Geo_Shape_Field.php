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
namespace Predis\Command\Argument\Search\Schema_Fields;

class Geo_Shape_Field extends Abstract_Field
{
    public const COORD_FLAT = 'FLAT';
    /**
     * @param bool|string $sortable
     * @param string|null $coordSystem Constants that represents available systems available on a class level.
     */
    public function __construct(string $identifier, string $alias = '', $sortable = self::NOT_SORTABLE, bool $no_index = false, ?string $coord_system = null)
    {
        $this->field_arguments[] = $identifier;
        if ($alias !== '') {
            $this->field_arguments[] = 'AS';
            $this->field_arguments[] = $alias;
        }
        $this->field_arguments[] = 'GEOSHAPE';
        if (null !== $coord_system) {
            $this->field_arguments[] = $coord_system;
        }
        if ($sortable === self::SORTABLE) {
            $this->field_arguments[] = 'SORTABLE';
        } elseif ($sortable === self::SORTABLE_UNF) {
            $this->field_arguments[] = 'SORTABLE';
            $this->field_arguments[] = 'UNF';
        }
        if ($no_index) {
            $this->field_arguments[] = 'NOINDEX';
        }
    }
}