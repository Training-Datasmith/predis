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

class Range_Arguments extends Common_Arguments
{
    public const AGG_SUM = 'sum';
    public const AGG_MIN = 'min';
    public const AGG_MAX = 'max';
    public const AGG_COUNT = 'count';
    public const AGG_COUNT_NAN = 'countNan';
    public const AGG_COUNT_ALL = 'countAll';
    /**
     * Filters samples by a list of specific timestamps.
     *
     * @return $this
     */
    public function filter_by_ts(int ...$ts): self
    {
        array_push($this->arguments, 'FILTER_BY_TS', ...$ts);
        return $this;
    }
    /**
     * Filters samples by minimum and maximum values.
     *
     * @return $this
     */
    public function filter_by_value(int $min, int $max): self
    {
        array_push($this->arguments, 'FILTER_BY_VALUE', $min, $max);
        return $this;
    }
    /**
     * Limits the number of returned samples.
     *
     * @return $this
     */
    public function count(int $count): self
    {
        array_push($this->arguments, 'COUNT', $count);
        return $this;
    }
    /**
     * Aggregates samples into time buckets.
     *
     * @param  string $aggregator      Aggregation type. Check class constants.
     * @param  int    $bucketDuration  Is duration of each bucket, in milliseconds.
     * @param  int    $align           It controls the time bucket timestamps by changing the reference timestamp on which a bucket is defined.
     * @param  int    $bucketTimestamp Controls how bucket timestamps are reported.
     * @param  bool   $empty           Is a flag, which, when specified, reports aggregations also for empty buckets.
     * @return $this
     */
    public function aggregation(string $aggregator, int $bucket_duration, int $align = 0, int $bucket_timestamp = 0, bool $empty = false): self
    {
        if ($align > 0) {
            array_push($this->arguments, 'ALIGN', $align);
        }
        array_push($this->arguments, 'AGGREGATION', $aggregator, $bucket_duration);
        if ($bucket_timestamp > 0) {
            array_push($this->arguments, 'BUCKETTIMESTAMP', $bucket_timestamp);
        }
        if (true === $empty) {
            $this->arguments[] = 'EMPTY';
        }
        return $this;
    }
}