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
class Scorer_Config implements Arrayable_Argument
{
    public const TYPE_BM25 = 'BM25';
    public const TYPE_TFIDF = 'TFIDF';
    public const TYPE_DISMAX = 'DISMAX';
    public const TYPE_DOCSCORE = 'DOCSCORE';
    /**
     * @var array
     */
    protected $arguments = [];
    /**
     * The text scoring algorithm. Defaults to BM25.
     *
     * @return $this
     */
    public function type(string $type = self::TYPE_BM25): self
    {
        $this->arguments[] = $type;
        return $this;
    }
    /**
     * An alias for the text score field in the results.
     * The aliased field will be included in the `value` object of each returned document.
     *
     * @return $this
     */
    public function as(string $alias): self
    {
        array_push($this->arguments, 'YIELD_SCORE_AS', $alias);
        return $this;
    }
    public function to_array(): array
    {
        return $this->arguments;
    }
}