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
namespace Predis\Consumer\Pub_Sub;

use Predis\Command\Processor\Key_Prefix_Processor;
use Predis\Consumer\Abstract_Dispatcher_Loop;
/**
 * Method-dispatcher loop built around the client-side abstraction of a Redis
 * PUB / SUB context.
 */
class Dispatcher_Loop extends Abstract_Dispatcher_Loop
{
    /**
     * @var Consumer
     */
    protected $consumer;
    public function __construct(Consumer $consumer)
    {
        $this->consumer = $consumer;
    }
    /**
     * Binds a callback to a channel.
     *
     * @param string   $messageType Channel name.
     * @param callable $callback    A callback.
     */
    public function attach_callback(string $message_type, callable $callback): void
    {
        $callback_name = $this->get_prefix_keys() . $message_type;
        $this->callbacks_dictionary[$callback_name] = $callback;
        if ($this->consumer->get_subscription_context()->get_context() === Subscription_Context::CONTEXT_SHARDED) {
            $this->consumer->ssubscribe($message_type);
        } else {
            $this->consumer->subscribe($message_type);
        }
    }
    /**
     * Stops listening to a channel and removes the associated callback.
     *
     * @param string $messageType Redis channel.
     */
    public function detach_callback(string $message_type): void
    {
        $callback_name = $this->get_prefix_keys() . $message_type;
        if (isset($this->callbacks_dictionary[$callback_name])) {
            unset($this->callbacks_dictionary[$callback_name]);
            if ($this->consumer->get_subscription_context()->get_context() === Subscription_Context::CONTEXT_SHARDED) {
                $this->consumer->sunsubscribe($message_type);
            } else {
                $this->consumer->unsubscribe($message_type);
            }
        }
    }
    /**
     * Starts the dispatcher loop.
     */
    public function run(): void
    {
        foreach ($this->consumer as $message) {
            $kind = $message->kind;
            if ($kind !== Consumer::MESSAGE && $kind !== Consumer::PMESSAGE) {
                if (isset($this->default_callback)) {
                    $callback = $this->default_callback;
                    $callback($message, $this);
                }
                continue;
            }
            if (isset($this->callbacks_dictionary[$message->channel])) {
                $callback = $this->callbacks_dictionary[$message->channel];
                $callback($message->payload, $this);
            } elseif (isset($this->default_callback)) {
                $callback = $this->default_callback;
                $callback($message, $this);
            }
        }
    }
    /**
     * Return the prefix used for keys.
     */
    protected function get_prefix_keys(): string
    {
        $options = $this->consumer->get_client()->get_options();
        if (isset($options->prefix)) {
            /** @var KeyPrefixProcessor $processor */
            $processor = $options->prefix;
            return $processor->get_prefix();
        }
        return '';
    }
}