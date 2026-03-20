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

use Predis\Not_Supported_Exception;
/**
 * Relay PUB/SUB consumer.
 */
class Relay_Consumer extends Consumer
{
    /**
     * Subscribes to the specified channels.
     *
     * @param string   ...$channel One or more channel names.
     * @param callable $callback   The message callback.
     */
    public function subscribe(string ...$channel): void
    {
        $channels = func_get_args();
        $callback = array_pop($channels);
        $this->status_flags |= self::STATUS_SUBSCRIBED;
        $command = $this->client->create_command('subscribe', [$channels, static function ($relay, $channel, $message) use ($callback): void {
            $callback((object) ['kind' => is_null($message) ? self::SUBSCRIBE : self::MESSAGE, 'channel' => $channel, 'payload' => $message], $relay);
        }]);
        $this->client->get_connection()->execute_command($command);
        $this->invalidate();
    }
    /**
     * Subscribes to the specified channels using a pattern.
     *
     * @param string   ...$pattern One or more channel name patterns.
     * @param callable $callback   The message callback.
     */
    public function psubscribe(...$pattern): void
    {
        $patterns = func_get_args();
        $callback = array_pop($patterns);
        $this->status_flags |= self::STATUS_PSUBSCRIBED;
        $command = $this->client->create_command('psubscribe', [$patterns, static function ($relay, $pattern, $channel, $message) use ($callback): void {
            $callback((object) ['kind' => is_null($message) ? self::PSUBSCRIBE : self::PMESSAGE, 'pattern' => $pattern, 'channel' => $channel, 'payload' => $message], $relay);
        }]);
        $this->client->get_connection()->execute_command($command);
        $this->invalidate();
    }
    /**
     * {@inheritDoc}
     */
    protected function generic_subscribe_init($subscribe_action)
    {
        if (isset($this->options[$subscribe_action])) {
            throw new Not_Supported_Exception('Relay does not support Pub/Sub constructor options.');
        }
    }
    /**
     * {@inheritDoc}
     */
    public function ping($payload = null)
    {
        throw new Not_Supported_Exception('Relay does not support PING in Pub/Sub.');
    }
    /**
     * {@inheritDoc}
     */
    public function stop($drop = false): bool
    {
        return false;
    }
}