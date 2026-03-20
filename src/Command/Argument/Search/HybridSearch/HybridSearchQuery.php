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
namespace Predis\Command\Argument\Search\Hybrid_Search;

use Predis\Command\Argument\Arrayable_Argument;
use Predis\Command\Argument\Search\Hybrid_Search\Combine\Linear_Combine_Config;
use Predis\Command\Argument\Search\Hybrid_Search\Combine\Rrf_Combine_Config;
use Predis\Command\Argument\Search\Hybrid_Search\Vector_Search\Knn_Vector_Search_Config;
use Predis\Command\Argument\Search\Hybrid_Search\Vector_Search\Range_Vector_Search_Config;
use Predis\Command\Redis\Utils\Command_Utility;
use Value_Error;
class Hybrid_Search_Query implements Arrayable_Argument
{
    public const SORT_ASC = 'ASC';
    public const SORT_DESC = 'DESC';
    /**
     * @var SearchConfig
     */
    protected $search_config;
    /**
     * The vector search portion of the query.
     *
     * @var KNNVectorSearchConfig|RangeVectorSearchConfig
     */
    protected $vector_search_config;
    /**
     * Configuration for the score fusion method (optional).
     * If not provided, Reciprocal Rank Fusion (RRF) is used with server-side default parameters.
     *
     * @var RRFCombineConfig|LinearCombineConfig
     */
    protected $combine_config;
    /**
     * @var array
     */
    protected $load = [];
    /**
     * @var array
     */
    protected $group_by = [];
    /**
     * @var array
     */
    protected $apply = [];
    /**
     * @var array
     */
    protected $sort_by = [];
    /**
     * @var string
     */
    protected $filter;
    /**
     * @var array
     */
    protected $limit = [];
    /**
     * @var array
     */
    protected $params = [];
    /**
     * @var bool
     */
    protected $explain_score = false;
    /**
     * @var bool
     */
    protected $timeout = false;
    /**
     * @var array
     */
    protected $with_cursor = [];
    /**
     * @var array
     */
    protected $arguments = [];
    /**
     * @param string $vectorSearchMethod Class type of desired vector search method
     * @param string $combineMethod      Class type of desired combine method
     */
    public function __construct(string $vector_search_method = Knn_Vector_Search_Config::class, string $combine_method = Rrf_Combine_Config::class)
    {
        $this->search_config = new Search_Config();
        $this->vector_search_config = new $vector_search_method();
        $this->combine_config = new $combine_method();
    }
    /**
     * @param  callable(SearchConfig): void $callable
     * @return $this
     */
    public function build_search_config(callable $callable): self
    {
        $callable($this->search_config);
        return $this;
    }
    /**
     * @param  callable(KNNVectorSearchConfig|RangeVectorSearchConfig): void $callable
     * @return $this
     */
    public function build_vector_search_config(callable $callable): self
    {
        $callable($this->vector_search_config);
        return $this;
    }
    /**
     * @param  callable(RRFCombineConfig|LinearCombineConfig): void $callable
     * @return $this
     */
    public function build_combine_config(callable $callable): self
    {
        $callable($this->combine_config);
        return $this;
    }
    /**
     * The list of fields to return in the results.
     *
     * @return $this
     */
    public function load(array $fields): self
    {
        array_push($this->load, 'LOAD', count($fields), ...$fields);
        return $this;
    }
    /**
     * @param  Reducer[] $reducers
     * @return $this
     */
    public function group_by(array $fields, array $reducers): self
    {
        array_push($this->group_by, 'GROUPBY', count($fields), ...$fields);
        foreach ($reducers as $reducer) {
            array_push($this->group_by, 'REDUCE', ...$reducer->to_array());
        }
        return $this;
    }
    /**
     * @param  array $expressionFieldDict field => function dictionary
     * @return $this
     */
    public function apply(array $expression_field_dict): self
    {
        foreach ($expression_field_dict as $field => $function) {
            array_push($this->apply, 'APPLY', $function, 'AS', $field);
        }
        return $this;
    }
    /**
     * Sorts the final results by a specific field.
     *
     * @param  array<string, string> $fields Dictionary with fields and sort direction. Check class constants.
     * @return $this
     */
    public function sort_by(array $fields): self
    {
        $fields_array = [];
        foreach ($fields as $field => $direction) {
            if (!in_array(strtoupper($direction), [self::SORT_ASC, self::SORT_DESC])) {
                throw new Value_Error('Sort direction must be one of "ASC" or "DESC".');
            }
            array_push($fields_array, $field, $direction);
        }
        array_push($this->sort_by, 'SORTBY', count($fields_array), ...$fields_array);
        return $this;
    }
    /**
     * Final result filtering.
     *
     * @return $this
     */
    public function filter(string $expression): self
    {
        $this->filter = $expression;
        return $this;
    }
    /**
     * @return $this
     */
    public function limit(int $offset, int $num): self
    {
        array_push($this->limit, 'LIMIT', $offset, $num);
        return $this;
    }
    /**
     * Binds values to named parameters in the query string.
     *
     * @return $this
     */
    public function params(array $params): self
    {
        $array_params = Command_Utility::dictionary_to_array($params);
        array_push($this->params, 'PARAMS', count($array_params), ...$array_params);
        return $this;
    }
    /**
     * @return $this
     */
    public function explain_score(): self
    {
        $this->explain_score = true;
        return $this;
    }
    /**
     * @return $this
     */
    public function timeout(): self
    {
        $this->timeout = true;
        return $this;
    }
    /**
     * @return $this
     */
    public function with_cursor(?int $read_size = null, ?int $idle_time = null): self
    {
        $this->with_cursor[] = 'WITHCURSOR';
        if ($read_size) {
            array_push($this->with_cursor, 'COUNT', $read_size);
        }
        if ($idle_time) {
            array_push($this->with_cursor, 'MAXIDLE', $idle_time);
        }
        return $this;
    }
    /**
     * {@inheritDoc}
     */
    public function to_array(): array
    {
        $this->arguments = array_merge($this->arguments, $this->search_config->to_array(), $this->vector_search_config->to_array());
        $combine_config = $this->combine_config->to_array();
        // Only add if any configuration was applied
        if (count($combine_config) > 2) {
            $this->arguments = array_merge($this->arguments, $combine_config);
        }
        if ($this->load) {
            $this->arguments = array_merge($this->arguments, $this->load);
        }
        if ($this->group_by) {
            $this->arguments = array_merge($this->arguments, $this->group_by);
        }
        if ($this->apply) {
            $this->arguments = array_merge($this->arguments, $this->apply);
        }
        if ($this->sort_by) {
            $this->arguments = array_merge($this->arguments, $this->sort_by);
        }
        if ($this->filter) {
            array_push($this->arguments, 'FILTER', $this->filter);
        }
        if ($this->limit) {
            $this->arguments = array_merge($this->arguments, $this->limit);
        }
        if ($this->params) {
            $this->arguments = array_merge($this->arguments, $this->params);
        }
        if ($this->explain_score) {
            $this->arguments[] = 'EXPLAINSCORE';
        }
        if ($this->timeout) {
            $this->arguments[] = 'TIMEOUT';
        }
        if ($this->with_cursor) {
            $this->arguments = array_merge($this->arguments, $this->with_cursor);
        }
        return $this->arguments;
    }
}