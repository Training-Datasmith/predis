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

namespace Predis\Command\Argument\Search\HybridSearch\VectorSearch;

use Predis\Command\Argument\ArrayableArgument;

abstract class BaseVectorSearchConfig implements ArrayableArgument
{
    public const POLICY_ADHOC = 'ADHOC';
    public const POLICY_BATCHES = 'BATCHES';
    public const POLICY_ACORN = 'ACORN';

    /**
     * @var array
     */
    protected $vector = [];

    /**
     * @var array
     */
    protected $filter = [];

    /**
     * @var array
     */
    protected $as = [];

    /**
     * @var array
     */
    protected $arguments = ['VSIM'];

    /**
     * Vector to perform search against.
     *
     * @param  string $field The vector field name to search against. Must start with "@".
     * @param  string $value Name of the parameter to use in the query. Must start with "$".
     */
    public function vector(string $field, string $value): self
    {
        array_push($this->vector, $field, $value);

        return $this;
    }

    /**
     * @return $this
     */
    public function filter(string $expression): self
    {
        array_push($this->filter, 'FILTER', $expression);

        return $this;
    }

    /**
     * @return $this
     */
    public function as(string $alias): self
    {
        array_push($this->as, 'YIELD_SCORE_AS', $alias);

        return $this;
    }

    abstract public function toArray(): array;
}
