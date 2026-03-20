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
class Search_Config implements Arrayable_Argument
{
    /**
     * @var array
     */
    protected $arguments = ['SEARCH'];
    /**
     * @var ScorerConfig
     */
    protected $scorer_config;
    public function __construct()
    {
        $this->scorer_config = new Scorer_Config();
    }
    /**
     * Search query.
     *
     * @return $this
     */
    public function query(string $query): self
    {
        $this->arguments[] = $query;
        return $this;
    }
    /**
     * @return $this
     */
    public function as(string $alias): self
    {
        array_push($this->arguments, 'YIELD_SCORE_AS', $alias);
        return $this;
    }
    /**
     * @param  callable(ScorerConfig): void $callable
     * @return $this
     */
    public function build_scorer_config(callable $callable): self
    {
        $callable($this->scorer_config);
        return $this;
    }
    public function to_array(): array
    {
        $scorer_config = $this->scorer_config->to_array();
        if (!empty($scorer_config)) {
            $this->arguments[] = 'SCORER';
            $this->arguments = array_merge($this->arguments, $scorer_config);
        }
        return $this->arguments;
    }
}