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
use Predis\Configuration\Options_Interface;
use Predis\Connection\Aggregate_Connection_Interface;
use Predis\Connection\Replication\Master_Slave_Replication;
use Predis\Connection\Replication\Sentinel_Replication;
/**
 * Configures an aggregate connection used for master/slave replication among
 * multiple Redis nodes.
 */
class Replication extends Aggregate
{
    /**
     * {@inheritdoc}
     */
    public function filter(Options_Interface $options, $value)
    {
        if (is_string($value)) {
            $value = $this->get_connection_initializer_by_string($options, $value);
        }
        if (is_callable($value)) {
            return $this->get_connection_initializer($options, $value);
        }
        throw new InvalidArgumentException(sprintf('%s expects either a string or a callable value, %s given', static::class, is_object($value) ? get_class($value) : gettype($value)));
    }
    /**
     * Returns a connection initializer (callable) from a descriptive string.
     *
     * Each connection initializer is specialized for the specified replication
     * backend so that all the necessary steps for the configuration of the new
     * aggregate connection are performed inside the initializer and the client
     * receives a ready-to-use connection.
     *
     * Supported configuration values are:
     *
     * - `predis` for unmanaged replication setups
     * - `redis-sentinel` for replication setups managed by redis-sentinel
     * - `sentinel` is an alias of `redis-sentinel`
     *
     * @param OptionsInterface $options     Client options
     * @param string           $description Identifier of a replication backend
     *
     * @return callable
     */
    protected function get_connection_initializer_by_string(Options_Interface $options, string $description)
    {
        switch ($description) {
            case 'sentinel':
            case 'redis-sentinel':
                return static function ($parameters, $options): \Predis\Connection\Replication\Sentinel_Replication {
                    return new Sentinel_Replication($options->service, $parameters, $options->connections);
                };
            case 'predis':
                return $this->get_default_connection_initializer();
            default:
                throw new InvalidArgumentException(sprintf('%s expects either `predis`, `sentinel` or `redis-sentinel` as valid string values, `%s` given', static::class, $description));
        }
    }
    /**
     * Returns the default connection initializer.
     *
     * @return callable
     */
    protected function get_default_connection_initializer()
    {
        return static function ($parameters, $options): \Predis\Connection\Replication\Master_Slave_Replication {
            $connection = new Master_Slave_Replication();
            if ($options->autodiscovery) {
                $connection->set_connection_factory($options->connections);
                $connection->set_auto_discovery(true);
            }
            return $connection;
        };
    }
    /**
     * {@inheritdoc}
     */
    public static function aggregate(Options_Interface $options, Aggregate_Connection_Interface $connection, array $nodes): void
    {
        if (!$connection instanceof Sentinel_Replication) {
            parent::aggregate($options, $connection, $nodes);
        }
    }
    /**
     * {@inheritdoc}
     */
    public function get_default(Options_Interface $options)
    {
        return $this->get_connection_initializer($options, $this->get_default_connection_initializer());
    }
}