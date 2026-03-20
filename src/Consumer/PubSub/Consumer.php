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

use Predis\Client_Exception;
use Predis\Client_Interface;
use Predis\Command\Command;
use Predis\Connection\Cluster\Cluster_Interface;
use Predis\Connection\Connection_Interface;
use Predis\Connection\Node_Connection_Interface;
use Predis\Consumer\Abstract_Consumer;
use Predis\Not_Supported_Exception;
/**
 * PUB/SUB consumer abstraction.
 */
class Consumer extends Abstract_Consumer
{
    public const SUBSCRIBE = 'subscribe';
    public const SSUBSCRIBE = 'ssubscribe';
    public const UNSUBSCRIBE = 'unsubscribe';
    public const SUNSUBSCRIBE = 'sunsubscribe';
    public const PSUBSCRIBE = 'psubscribe';
    public const PUNSUBSCRIBE = 'punsubscribe';
    public const MESSAGE = 'message';
    public const PMESSAGE = 'pmessage';
    public const PONG = 'pong';
    public const STATUS_VALID = 1;
    // 0b0001
    public const STATUS_SUBSCRIBED = 2;
    // 0b0010
    public const STATUS_PSUBSCRIBED = 4;
    // 0b0100
    public const STATUS_SSUBSCRIBED = 8;
    // 0b1000
    protected $status_flags = self::STATUS_VALID;
    protected $options;
    /**
     * @var SubscriptionContext
     */
    private $subscription_context;
    /**
     * @param  ClientInterface       $client  Client instance used by the consumer.
     * @param  array|null            $options Options for the consumer initialization.
     * @throws NotSupportedException
     */
    public function __construct(Client_Interface $client, ?array $options = null)
    {
        $this->options = $options ?: [];
        $this->set_subscription_context($client->get_connection());
        parent::__construct($client);
        $this->check_capabilities($client);
        $this->client = $client;
        $this->generic_subscribe_init('subscribe');
        $this->generic_subscribe_init('ssubscribe');
        $this->generic_subscribe_init('psubscribe');
    }
    /**
     * Returns subscription context for current instance.
     */
    public function get_subscription_context(): Subscription_Context
    {
        return $this->subscription_context;
    }
    /**
     * Checks if the client instance satisfies the required conditions needed to
     * initialize a PUB/SUB consumer.
     *
     * @param ClientInterface $client Client instance used by the consumer.
     *
     * @throws NotSupportedException
     */
    private function check_capabilities(Client_Interface $client): void
    {
        $commands = ['publish', 'spublish', 'subscribe', 'ssubscribe', 'unsubscribe', 'sunsubscribe', 'psubscribe', 'punsubscribe'];
        if (!$client->get_command_factory()->supports(...$commands)) {
            throw new Not_Supported_Exception('PUB/SUB commands are not supported by the current command factory.');
        }
    }
    /**
     * This method shares the logic to handle SUBSCRIBE, SSUBSCRIBE, PSUBSCRIBE.
     *
     * @param string $subscribeAction Type of subscription.
     */
    private function generic_subscribe_init(string $subscribe_action): void
    {
        if (isset($this->options[$subscribe_action])) {
            $this->{$subscribe_action}($this->options[$subscribe_action]);
        }
    }
    /**
     * {@inheritdoc}
     */
    protected function write_request($method, array $arguments)
    {
        $this->client->get_connection()->write_request($this->client->create_command($method, Command::normalize_arguments($arguments)));
    }
    /**
     * Automatically stops the consumer when the garbage collector kicks in.
     */
    public function __destruct()
    {
        $this->stop(true);
    }
    /**
     * Checks if the specified flag is valid based on the state of the consumer.
     *
     * @param int $value Flag.
     */
    protected function is_flag_set($value): bool
    {
        return ($this->status_flags & $value) === $value;
    }
    /**
     * Subscribes to the specified channels.
     *
     * @param string ...$channels One or more channel names.
     */
    public function subscribe(string ...$channels): void
    {
        $this->write_request(self::SUBSCRIBE, func_get_args());
        $this->status_flags |= self::STATUS_SUBSCRIBED;
    }
    /**
     * Subscribes to the specified shard channels.
     */
    public function ssubscribe(string ...$channels): void
    {
        $this->write_request(self::SSUBSCRIBE, func_get_args());
        $this->status_flags |= self::STATUS_SSUBSCRIBED;
    }
    /**
     * Unsubscribes from the specified channels.
     *
     * @param string ...$channel One or more channel names.
     */
    public function unsubscribe(...$channel): void
    {
        $this->write_request(self::UNSUBSCRIBE, func_get_args());
    }
    /**
     * Unsubscribes from the specified shard channels.
     */
    public function sunsubscribe(string ...$channels): void
    {
        $this->write_request(self::SUNSUBSCRIBE, func_get_args());
    }
    /**
     * Subscribes to the specified channels using a pattern.
     *
     * @param string ...$pattern One or more channel name patterns.
     */
    public function psubscribe(...$pattern): void
    {
        $this->write_request(self::PSUBSCRIBE, func_get_args());
        $this->status_flags |= self::STATUS_PSUBSCRIBED;
    }
    /**
     * Unsubscribes from the specified channels using a pattern.
     *
     * @param string ...$pattern One or more channel name patterns.
     */
    public function punsubscribe(...$pattern): void
    {
        $this->write_request(self::PUNSUBSCRIBE, func_get_args());
    }
    /**
     * PING the server with an optional payload that will be echoed as a
     * PONG message in the pub/sub loop.
     *
     * @param string $payload Optional PING payload.
     */
    public function ping($payload = null): void
    {
        $this->write_request('PING', [$payload]);
    }
    /**
     * Closes the context by unsubscribing from all the subscribed channels. The
     * context can be forcefully closed by dropping the underlying connection.
     *
     * @param bool $drop Indicates if the context should be closed by dropping the connection.
     *
     * @return bool Returns false when there are no pending messages.
     */
    public function stop(bool $drop = false): bool
    {
        if (!$this->valid()) {
            return false;
        }
        if ($drop) {
            $this->invalidate();
            $this->disconnect();
        } else {
            if ($this->is_flag_set(self::STATUS_SUBSCRIBED)) {
                $this->unsubscribe();
            }
            if ($this->is_flag_set(self::STATUS_PSUBSCRIBED)) {
                $this->punsubscribe();
            }
            if ($this->is_flag_set(self::STATUS_SSUBSCRIBED)) {
                $this->sunsubscribe();
            }
        }
        return !$drop;
    }
    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->get_value();
    }
    /**
     * Checks if the consumer is still in a valid state to continue.
     */
    public function valid(): bool
    {
        $is_valid = $this->is_flag_set(self::STATUS_VALID);
        $subscription_flags = self::STATUS_SUBSCRIBED | self::STATUS_PSUBSCRIBED | self::STATUS_SSUBSCRIBED;
        $has_subscriptions = ($this->status_flags & $subscription_flags) > 0;
        return $is_valid && $has_subscriptions;
    }
    /**
     * Resets the state of the consumer.
     */
    protected function invalidate()
    {
        $this->status_flags = 0;
        // 0b0000;
    }
    /**
     * {@inheritdoc}
     */
    protected function disconnect()
    {
        $this->client->disconnect();
    }
    /**
     * {@inheritdoc}
     */
    protected function get_value()
    {
        /** @var NodeConnectionInterface $connection */
        $connection = $this->client->get_connection();
        $response = $connection->read();
        switch ($response[0]) {
            case self::SUBSCRIBE:
            case self::SSUBSCRIBE:
            case self::UNSUBSCRIBE:
            case self::SUNSUBSCRIBE:
            case self::PSUBSCRIBE:
            case self::PUNSUBSCRIBE:
                if ($response[2] === 0) {
                    $this->invalidate();
                }
            // The missing break here is intentional as we must process
            // subscriptions and unsubscriptions as standard messages.
            // no break
            case self::MESSAGE:
                return (object) ['kind' => $response[0], 'channel' => $response[1], 'payload' => $response[2]];
            case self::PMESSAGE:
                return (object) ['kind' => $response[0], 'pattern' => $response[1], 'channel' => $response[2], 'payload' => $response[3]];
            case self::PONG:
                return (object) ['kind' => $response[0], 'payload' => $response[1]];
            default:
                throw new Client_Exception("Unknown message type '{$response[0]}' received in the PUB/SUB context.");
        }
    }
    /**
     * Set subscription context depends on connection.
     *
     * @param  NodeConnectionInterface $connection
     */
    private function set_subscription_context(Connection_Interface $connection): void
    {
        if ($connection instanceof Cluster_Interface) {
            $this->subscription_context = new Subscription_Context(Subscription_Context::CONTEXT_SHARDED);
        } else {
            $this->subscription_context = new Subscription_Context();
        }
    }
}