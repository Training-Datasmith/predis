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
namespace Predis\Command\Argument\Search\Hybrid_Search\Combine;

class Rrf_Combine_Config extends Base_Combine
{
    /**
     * @var int
     */
    protected $window;
    /**
     * @var int
     */
    protected $rrf_constant;
    /**
     * The number of top results from each search type to consider for fusion. Defaults to 50.
     *
     * @return $this
     */
    public function window(int $window): self
    {
        $this->window = $window;
        return $this;
    }
    /**
     * The RRF ranking constant. A smaller value gives more weight to top-ranked items. Defaults to 60.
     *
     * @return $this
     */
    public function rrf_constant(int $constant): self
    {
        $this->rrf_constant = $constant;
        return $this;
    }
    /**
     * {@inheritDoc}
     */
    public function to_array(): array
    {
        $this->arguments[] = 'RRF';
        $tokens = [];
        if ($this->window !== null) {
            array_push($tokens, 'WINDOW', $this->window);
        }
        if ($this->rrf_constant !== null) {
            array_push($tokens, 'CONSTANT', $this->rrf_constant);
        }
        if ($this->as) {
            array_push($tokens, ...$this->as);
        }
        if (!empty($tokens)) {
            array_push($this->arguments, count($tokens), ...$tokens);
        }
        return $this->arguments;
    }
}