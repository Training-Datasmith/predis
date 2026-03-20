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
namespace Predis\Retry\Strategy;

class Exponential_Backoff implements Retry_Strategy_Interface
{
    /**
     * @var int
     */
    protected $base;
    /**
     * @var int
     */
    protected $cap;
    /**
     * @var bool
     */
    protected $with_jitter;
    /**
     * @param int  $base       in micro seconds
     * @param int  $cap        in micro seconds
     */
    public function __construct(int $base = self::DEFAULT_BASE, int $cap = self::DEFAULT_CAP, bool $with_jitter = false)
    {
        $this->base = $base;
        $this->cap = $cap;
        $this->with_jitter = $with_jitter;
    }
    /**
     * {@inheritDoc}
     */
    public function compute(int $failures): int
    {
        if ($this->with_jitter) {
            return min($this->cap, mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax() * ($this->base * 2 ** $failures));
        }
        if ($this->cap > 0) {
            return min($this->cap, $this->base * 2 ** $failures);
        }
        return $this->base * 2 ** $failures;
    }
    public function get_base(): int
    {
        return $this->base;
    }
    public function get_cap(): int
    {
        return $this->cap;
    }
}