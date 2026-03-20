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
namespace Predis\Connection;

trait Relay_Methods
{
    /**
     * Registers a new `flushed` event listener.
     *
     * @param  callable $callback
     * @return bool
     */
    public function on_flushed(?callable $callback)
    {
        return $this->client->on_flushed($callback);
    }
    /**
     * Registers a new `invalidated` event listener.
     *
     * @param  callable    $callback
     * @return bool
     */
    public function on_invalidated(?callable $callback, ?string $pattern = null)
    {
        return $this->client->on_invalidated($callback, $pattern);
    }
    /**
     * Dispatches all pending events.
     *
     * @return int|false
     */
    public function dispatch_events()
    {
        return $this->client->dispatch_events();
    }
    /**
     * Adds ignore pattern(s). Matching keys will not be cached in memory.
     *
     * @param  string $pattern,...
     * @return int
     */
    public function add_ignore_patterns(string ...$pattern)
    {
        return $this->client->add_ignore_patterns(...$pattern);
    }
    /**
     * Adds allow pattern(s). Only matching keys will be cached in memory.
     *
     * @param  string $pattern,...
     * @return int
     */
    public function add_allow_patterns(string ...$pattern)
    {
        return $this->client->add_allow_patterns(...$pattern);
    }
    /**
     * Returns the connection's endpoint identifier.
     *
     * @return string|false
     */
    public function endpoint_id()
    {
        return $this->client->endpoint_id();
    }
    /**
     * Returns a unique representation of the underlying socket connection identifier.
     *
     * @return string|false
     */
    public function socket_id()
    {
        return $this->client->socket_id();
    }
    /**
     * Returns information about the license.
     *
     * @return array<string, mixed>
     */
    public function license()
    {
        return $this->client->license();
    }
    /**
     * Returns statistics about Relay.
     *
     * @return array<string, array<string, mixed>>
     */
    public function stats()
    {
        return $this->client->stats();
    }
    /**
     * Returns the number of bytes allocated, or `0` in client-only mode.
     *
     * @return int
     */
    public function max_memory()
    {
        return $this->client->max_memory();
    }
    /**
     * Flushes Relay's in-memory cache of all databases.
     * When given an endpoint, only that connection will be flushed.
     * When given an endpoint and database index, only that database
     * for that connection will be flushed.
     *
     * @return bool
     */
    public function flush_memory(?string $endpoint_id = null, ?int $db = null)
    {
        return $this->client->flush_memory($endpoint_id, $db);
    }
}