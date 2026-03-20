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
namespace Predis\Command\Argument\Time_Series;

use Predis\Command\Argument\Arrayable_Argument;
use UnexpectedValueException;
class Common_Arguments implements Arrayable_Argument
{
    public const POLICY_BLOCK = 'BLOCK';
    public const POLICY_FIRST = 'FIRST';
    public const POLICY_LAST = 'LAST';
    public const POLICY_MIN = 'MIN';
    public const POLICY_MAX = 'MAX';
    public const POLICY_SUM = 'SUM';
    public const ENCODING_UNCOMPRESSED = 'UNCOMPRESSED';
    public const ENCODING_COMPRESSED = 'COMPRESSED';
    /**
     * @var array
     */
    protected $arguments = [];
    /**
     * Is maximum age for samples compared to the highest reported timestamp, in milliseconds.
     *
     * @return $this
     */
    public function retention_msecs(int $retention_period): self
    {
        array_push($this->arguments, 'RETENTION', $retention_period);
        return $this;
    }
    /**
     * Ignore samples with given time or value difference.
     *
     * @param  int   $maxTimeDiff Non-negative integer value in milliseconds
     * @param  float $maxValDiff  Non-negative float value
     * @return $this
     */
    public function ignore(int $max_time_diff, float $max_val_diff): self
    {
        if ($max_time_diff < 0 || $max_val_diff < 0) {
            throw new UnexpectedValueException('Ignore does not accept negative values');
        }
        array_push($this->arguments, 'IGNORE', $max_time_diff, $max_val_diff);
        return $this;
    }
    /**
     * Is initial allocation size, in bytes, for the data part of each new chunk.
     *
     * @return $this
     */
    public function chunk_size(int $size): self
    {
        array_push($this->arguments, 'CHUNK_SIZE', $size);
        return $this;
    }
    /**
     * Is policy for handling insertion of multiple samples with identical timestamps.
     *
     * @return $this
     */
    public function duplicate_policy(string $policy = self::POLICY_BLOCK): self
    {
        array_push($this->arguments, 'DUPLICATE_POLICY', $policy);
        return $this;
    }
    /**
     * Is set of label-value pairs that represent metadata labels of the key and serve as a secondary index.
     *
     * @param  mixed ...$labelValuePair
     * @return $this
     */
    public function labels(...$label_value_pair): self
    {
        array_push($this->arguments, 'LABELS', ...$label_value_pair);
        return $this;
    }
    /**
     * Specifies the series samples encoding format.
     *
     * @return $this
     */
    public function encoding(string $encoding = self::ENCODING_COMPRESSED): self
    {
        array_push($this->arguments, 'ENCODING', $encoding);
        return $this;
    }
    /**
     * Is used when a time series is a compaction.
     * With LATEST, TS.GET reports the compacted value of the latest, possibly partial, bucket.
     *
     * @return $this
     */
    public function latest(): self
    {
        $this->arguments[] = 'LATEST';
        return $this;
    }
    /**
     * Includes in the reply all label-value pairs representing metadata labels of the time series.
     *
     * @return $this
     */
    public function with_labels(): self
    {
        $this->arguments[] = 'WITHLABELS';
        return $this;
    }
    /**
     * Returns a subset of the label-value pairs that represent metadata labels of the time series.
     *
     * @return $this
     */
    public function selected_labels(string ...$labels): self
    {
        array_push($this->arguments, 'SELECTED_LABELS', ...$labels);
        return $this;
    }
    /**
     * {@inheritDoc}
     */
    public function to_array(): array
    {
        return $this->arguments;
    }
}