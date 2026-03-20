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

class Numeric_Field extends Abstract_Field
{
    /**
     * @param bool|string $sortable
     */
    public function __construct(string $identifier, string $alias = '', $sortable = self::NOT_SORTABLE, bool $no_index = false, bool $allows_missing = false)
    {
        $this->set_common_options('NUMERIC', $identifier, $alias, $sortable, $no_index, $allows_missing);
    }
}