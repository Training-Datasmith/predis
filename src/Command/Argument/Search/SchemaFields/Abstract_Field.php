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

abstract class Abstract_Field implements Field_Interface
{
    public const SORTABLE = true;
    public const NOT_SORTABLE = false;
    public const SORTABLE_UNF = 'UNF';
    /**
     * @var array
     */
    protected $field_arguments = [];
    /**
     * @param  bool|string $sortable
     */
    protected function set_common_options(string $field_type, string $identifier, string $alias = '', $sortable = self::NOT_SORTABLE, bool $no_index = false, bool $allows_missing = false): void
    {
        $this->field_arguments[] = $identifier;
        if ($alias !== '') {
            $this->field_arguments[] = 'AS';
            $this->field_arguments[] = $alias;
        }
        $this->field_arguments[] = $field_type;
        if ($sortable === self::SORTABLE) {
            $this->field_arguments[] = 'SORTABLE';
        } elseif ($sortable === self::SORTABLE_UNF) {
            $this->field_arguments[] = 'SORTABLE';
            $this->field_arguments[] = 'UNF';
        }
        if ($no_index) {
            $this->field_arguments[] = 'NOINDEX';
        }
        if ($allows_missing) {
            $this->field_arguments[] = 'INDEXMISSING';
        }
    }
    /**
     * {@inheritDoc}
     */
    public function to_array(): array
    {
        return $this->field_arguments;
    }
}