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
namespace Predis\Command\Argument\Search;

use InvalidArgumentException;
class Spellcheck_Arguments extends Common_Arguments
{
    /**
     * @var string[]
     */
    private $terms_enum = ['include' => 'INCLUDE', 'exclude' => 'EXCLUDE'];
    /**
     * Is maximum Levenshtein distance for spelling suggestions (default: 1, max: 4).
     *
     * @return $this
     */
    public function distance(int $distance): self
    {
        $this->arguments[] = 'DISTANCE';
        $this->arguments[] = $distance;
        return $this;
    }
    /**
     * Specifies an inclusion (INCLUDE) or exclusion (EXCLUDE) of a custom dictionary named {dict}.
     *
     * @return $this
     */
    public function terms(string $dictionary, string $modifier = 'INCLUDE', string ...$terms): self
    {
        if (!in_array(strtoupper($modifier), $this->terms_enum)) {
            $enum_values = implode(', ', array_values($this->terms_enum));
            throw new InvalidArgumentException("Wrong modifier value given. Currently supports: {$enum_values}");
        }
        array_push($this->arguments, 'TERMS', $this->terms_enum[strtolower($modifier)], $dictionary, ...$terms);
        return $this;
    }
}