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

class Vector_Field extends Abstract_Field
{
    /**
     * @var array
     */
    protected $field_arguments = [];
    public function __construct(string $field_name, string $algorithm, array $attribute_name_value_dictionary, string $alias = '')
    {
        $this->set_common_options('VECTOR', $field_name, $alias);
        array_push($this->field_arguments, $algorithm, count($attribute_name_value_dictionary));
        $this->field_arguments = array_merge($this->field_arguments, $attribute_name_value_dictionary);
    }
    /**
     * {@inheritDoc}
     */
    public function to_array(): array
    {
        return $this->field_arguments;
    }
}