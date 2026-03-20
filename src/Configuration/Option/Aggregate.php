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
namespace Predis\Configuration\Option;

use InvalidArgumentException;
use Predis\Configuration\Option_Interface;
use Predis\Configuration\Options_Interface;
use Predis\Connection\Aggregate_Connection_Interface;
use Predis\Connection\Node_Connection_Interface;
/**
 * Client option for configuring generic aggregate connections.
 *
 * The only value accepted by this option is a callable that must return a valid
 * connection instance of Predis\Connection\AggregateConnectionInterface when
 * invoked by the client to create a new aggregate connection instance.
 *
 * Creation and configuration of the aggregate connection is up to the user.
 */
class Aggregate implements Option_Interface
{
    /**
     * {@inheritdoc}
     */
    public function filter(Options_Interface $options, $value)
    {
        if (!is_callable($value)) {
            throw new InvalidArgumentException(sprintf('%s expects a callable object acting as an aggregate connection initializer', static::class));
        }
        return $this->get_connection_initializer($options, $value);
    }
    /**
     * Wraps a user-supplied callable used to create a new aggregate connection.
     *
     * When the original callable acting as a connection initializer is executed
     * by the client to create a new aggregate connection, it will receive the
     * following arguments:
     *
     * - $parameters (same as passed to Predis\Client::__construct())
     * - $options (options container, Predis\Configuration\OptionsInterface)
     * - $option (current option, Predis\Configuration\OptionInterface)
     *
     * The original callable must return a valid aggregation connection instance
     * of type Predis\Connection\AggregateConnectionInterface, this is enforced
     * by the wrapper returned by this method and an exception is thrown when
     * invalid values are returned.
     *
     * @param OptionsInterface $options  Client options
     * @param callable         $callable Callable initializer
     *
     * @return callable
     * @throws InvalidArgumentException
     */
    protected function get_connection_initializer(Options_Interface $options, callable $callable)
    {
        return function ($parameters = null, $autoaggregate = false) use ($callable, $options): \Predis\Connection\Aggregate_Connection_Interface {
            $connection = call_user_func_array($callable, [&$parameters, $options, $this]);
            if (!$connection instanceof Aggregate_Connection_Interface) {
                throw new InvalidArgumentException(sprintf('%s expects the supplied callable to return an instance of %s, but %s was returned', static::class, Aggregate_Connection_Interface::class, is_object($connection) ? get_class($connection) : gettype($connection)));
            }
            if ($parameters && $autoaggregate) {
                static::aggregate($options, $connection, $parameters);
            }
            return $connection;
        };
    }
    /**
     * Adds single connections to an aggregate connection instance.
     *
     * @param OptionsInterface             $options    Client options
     * @param AggregateConnectionInterface $connection Target aggregate connection
     * @param array                        $nodes      List of nodes to be added to the target aggregate connection
     */
    public static function aggregate(Options_Interface $options, Aggregate_Connection_Interface $connection, array $nodes): void
    {
        $connections = $options->connections;
        foreach ($nodes as $node) {
            $connection->add($node instanceof Node_Connection_Interface ? $node : $connections->create($node));
        }
    }
    /**
     * {@inheritdoc}
     */
    public function get_default(Options_Interface $options)
    {
    }
}