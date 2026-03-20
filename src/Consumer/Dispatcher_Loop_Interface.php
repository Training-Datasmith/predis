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
namespace Predis\Consumer;

/**
 * Abstraction around consumer interface to invoke callbacks on received messages.
 */
interface Dispatcher_Loop_Interface
{
    /**
     * Returns consumer interface instance.
     */
    public function get_consumer(): Consumer_Interface;
    /**
     * Sets default callback that invokes if message type have no matching callback.
     */
    public function set_default_callback(?callable $callback = null): void;
    /**
     * Binds given message type to given callback.
     */
    public function attach_callback(string $message_type, callable $callback): void;
    /**
     * Removes connection between given message type and previously assigned callback.
     */
    public function detach_callback(string $message_type): void;
    /**
     * Starts consumer loop.
     */
    public function run(): void;
    /**
     * Stops consumer loop.
     */
    public function stop(): void;
}