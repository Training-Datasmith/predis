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
namespace Predis\Retry;

use Predis\Connection\Connection_Exception;
use Predis\Connection\Resource\Exception\Stream_Init_Exception;
use Predis\Retry\Strategy\Retry_Strategy_Interface;
use Predis\Timeout_Exception;
use Throwable;
class Retry
{
    /**
     * @var RetryStrategyInterface
     */
    protected $backoff_strategy;
    /**
     * @var int
     */
    protected $retries;
    /**
     * @var array
     */
    protected $catchable_exceptions = [Timeout_Exception::class, Connection_Exception::class, Stream_Init_Exception::class];
    /**
     * @param array|null             $catchableExceptions A list of exceptions classes that should be caught.
     *                                                    Overrides default list of the catchable exceptions.
     */
    public function __construct(Retry_Strategy_Interface $backoff_strategy, int $retries, ?array $catchable_exceptions = null)
    {
        $this->backoff_strategy = $backoff_strategy;
        $this->retries = $retries;
        if (null !== $catchable_exceptions) {
            $this->catchable_exceptions = $catchable_exceptions;
        }
    }
    /**
     * Update the retry count.
     */
    public function update_retries_count(int $retries): void
    {
        $this->retries = $retries;
    }
    /**
     * Extend catchable exceptions list.
     */
    public function update_catchable_exceptions(array $catchable_exceptions): void
    {
        $this->catchable_exceptions = array_merge($this->catchable_exceptions, $catchable_exceptions);
    }
    public function get_retries(): int
    {
        return $this->retries;
    }
    public function get_strategy(): Retry_Strategy_Interface
    {
        return $this->backoff_strategy;
    }
    /**
     * @param  callable(): mixed              $do
     * @param  callable(Throwable): void|null $fail
     * @return mixed
     * @throws Throwable
     */
    public function call_with_retry(callable $do, ?callable $fail = null)
    {
        $failures = 0;
        while (true) {
            try {
                return $do();
            } catch (Throwable $e) {
                if (null !== $this->catchable_exceptions) {
                    $is_catchable = false;
                    foreach ($this->catchable_exceptions as $catchable_exception) {
                        if ($e instanceof $catchable_exception) {
                            $is_catchable = true;
                        }
                    }
                    if (!$is_catchable) {
                        throw $e;
                    }
                }
                $backoff = $this->backoff_strategy->compute($failures);
                ++$failures;
                if ($this->retries >= 0 && $failures > $this->retries) {
                    throw $e;
                }
                if ($fail !== null) {
                    $fail($e);
                }
                if ($backoff > 0) {
                    usleep($backoff);
                }
            }
        }
    }
}