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
namespace Predis\Consumer\Push;

use Predis\Consumer\Abstract_Dispatcher_Loop;
class Dispatcher_Loop extends Abstract_Dispatcher_Loop
{
    public function __construct(Consumer $consumer)
    {
        $this->consumer = $consumer;
    }
    /**
     * {@inheritDoc}
     */
    public function run(): void
    {
        foreach ($this->consumer as $notification) {
            if (null !== $notification) {
                $message_type = $notification->get_data_type();
                if (isset($this->callbacks_dictionary[$message_type])) {
                    $callback = $this->callbacks_dictionary[$message_type];
                    $callback($notification->get_payload(), $this);
                } elseif (isset($this->default_callback)) {
                    $callback = $this->default_callback;
                    $callback($notification->get_payload(), $this);
                }
            }
        }
    }
}