<?php

declare(strict_types=1);

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Consumer;

/**
 * Abstraction around consumer interface to invoke callbacks on received messages.
 */
interface DispatcherLoopInterface
{
    /**
     * Returns consumer interface instance.
     */
    public function getConsumer(): ConsumerInterface;

    /**
     * Sets default callback that invokes if message type have no matching callback.
     */
    public function setDefaultCallback(?callable $callback = null): void;

    /**
     * Binds given message type to given callback.
     */
    public function attachCallback(string $messageType, callable $callback): void;

    /**
     * Removes connection between given message type and previously assigned callback.
     */
    public function detachCallback(string $messageType): void;

    /**
     * Starts consumer loop.
     */
    public function run(): void;

    /**
     * Stops consumer loop.
     */
    public function stop(): void;
}
