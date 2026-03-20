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

abstract class Abstract_Dispatcher_Loop implements Dispatcher_Loop_Interface
{
    /**
     * @var ConsumerInterface
     */
    protected $consumer;
    /**
     * @var callable|null
     */
    protected $default_callback;
    /**
     * @var callable[]
     */
    protected $callbacks_dictionary;
    /**
     * {@inheritDoc}
     */
    public function __construct(Consumer_Interface $consumer)
    {
        $this->consumer = $consumer;
    }
    /**
     * {@inheritDoc}
     */
    public function get_consumer(): Consumer_Interface
    {
        return $this->consumer;
    }
    /**
     * {@inheritDoc}
     */
    public function set_default_callback(?callable $callback = null): void
    {
        $this->default_callback = $callback;
    }
    /**
     * {@inheritDoc}
     */
    public function attach_callback(string $message_type, callable $callback): void
    {
        $this->callbacks_dictionary[$message_type] = $callback;
    }
    /**
     * {@inheritDoc}
     */
    public function detach_callback(string $message_type): void
    {
        if (isset($this->callbacks_dictionary[$message_type])) {
            unset($this->callbacks_dictionary[$message_type]);
        }
    }
    /**
     * {@inheritDoc}
     */
    abstract public function run(): void;
    /**
     * {@inheritDoc}
     */
    public function stop(): void
    {
        $this->consumer->stop();
    }
}