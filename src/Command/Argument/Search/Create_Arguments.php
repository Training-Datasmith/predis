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
class Create_Arguments extends Common_Arguments
{
    /**
     * @var string[]
     */
    private $supported_data_types_enum = ['hash' => 'HASH', 'json' => 'JSON'];
    /**
     * Specify data type for given index. To index JSON you must have the RedisJSON module to be installed.
     *
     * @return $this
     */
    public function on(string $modifier = 'HASH'): self
    {
        if (in_array(strtoupper($modifier), $this->supported_data_types_enum)) {
            $this->arguments[] = 'ON';
            $this->arguments[] = $this->supported_data_types_enum[strtolower($modifier)];
            return $this;
        }
        $enum_values = implode(', ', array_values($this->supported_data_types_enum));
        throw new InvalidArgumentException("Wrong modifier value given. Currently supports: {$enum_values}");
    }
    /**
     * Adds one or more prefixes into index.
     *
     * @return $this
     */
    public function prefix(array $prefixes): self
    {
        $this->arguments[] = 'PREFIX';
        $this->arguments[] = count($prefixes);
        $this->arguments = array_merge($this->arguments, $prefixes);
        return $this;
    }
    /**
     * Document attribute set as document language.
     *
     * @return $this
     */
    public function language_field(string $language_attribute): self
    {
        $this->arguments[] = 'LANGUAGE_FIELD';
        $this->arguments[] = $language_attribute;
        return $this;
    }
    /**
     * Default score for documents in the index.
     *
     * @return $this
     */
    public function score(float $default_score = 1.0): self
    {
        $this->arguments[] = 'SCORE';
        $this->arguments[] = $default_score;
        return $this;
    }
    /**
     * Document attribute that used as the document rank based on the user ranking.
     *
     * @return $this
     */
    public function score_field(string $score_attribute): self
    {
        $this->arguments[] = 'SCORE_FIELD';
        $this->arguments[] = $score_attribute;
        return $this;
    }
    /**
     * Forces RediSearch to encode indexes as if there were more than 32 text attributes.
     *
     * @return $this
     */
    public function max_text_fields(): self
    {
        $this->arguments[] = 'MAXTEXTFIELDS';
        return $this;
    }
    /**
     * Does not store term offsets for documents.
     *
     * @return $this
     */
    public function no_offsets(): self
    {
        $this->arguments[] = 'NOOFFSETS';
        return $this;
    }
    /**
     * Creates a lightweight temporary index that expires after a specified period of inactivity, in seconds.
     *
     * @return $this
     */
    public function temporary(int $seconds): self
    {
        $this->arguments[] = 'TEMPORARY';
        $this->arguments[] = $seconds;
        return $this;
    }
    /**
     * Conserves storage space and memory by disabling highlighting support.
     *
     * @return $this
     */
    public function no_hl(): self
    {
        $this->arguments[] = 'NOHL';
        return $this;
    }
    /**
     * Does not store attribute bits for each term.
     *
     * @return $this
     */
    public function no_fields(): self
    {
        $this->arguments[] = 'NOFIELDS';
        return $this;
    }
    /**
     * Avoids saving the term frequencies in the index.
     *
     * @return $this
     */
    public function no_freqs(): self
    {
        $this->arguments[] = 'NOFREQS';
        return $this;
    }
    /**
     * Sets the index with a custom stopword list, to be ignored during indexing and search time.
     *
     * @return $this
     */
    public function stop_words(array $stop_words): self
    {
        $this->arguments[] = 'STOPWORDS';
        $this->arguments[] = count($stop_words);
        $this->arguments = array_merge($this->arguments, $stop_words);
        return $this;
    }
}