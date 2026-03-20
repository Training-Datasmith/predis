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
use Predis\Cluster\Redis_Strategy;
use Predis\Configuration\Options_Interface;
use Predis\Connection\Cluster\Predis_Cluster;
use Predis\Connection\Cluster\Redis_Cluster;
use Predis\Connection\Parameters;
/**
 * Configures an aggregate connection used for clustering
 * multiple Redis nodes using various implementations with
 * different algorithms or strategies.
 */
class Cluster extends Aggregate
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
     * Returns a connection initializer from a descriptive name.
     *
     * @param OptionsInterface $options     Client options
     * @param string           $description Identifier of a replication backend (`predis`, `sentinel`)
     *
     * @return callable
     */
    protected function get_connection_initializer_by_string(Options_Interface $options, string $description)
    {
        switch ($description) {
            case 'redis':
            case 'redis-cluster':
                return static function ($parameters, $options, $option): \Predis\Connection\Cluster\Redis_Cluster {
                    $option_parameters = $options->parameters ?? [];
                    return new Redis_Cluster($options->connections, new Parameters($option_parameters), new Redis_Strategy($options->crc16), $options->read_timeout);
                };
            case 'predis':
                return $this->get_default_connection_initializer();
            default:
                throw new InvalidArgumentException(sprintf('%s expects either `predis`, `redis` or `redis-cluster` as valid string values, `%s` given', static::class, $description));
        }
    }
    /**
     * Returns the default connection initializer.
     *
     * @return callable
     */
    protected function get_default_connection_initializer()
    {
        return static function ($parameters, $options, $option): \Predis\Connection\Cluster\Predis_Cluster {
            $options_parameters = $options->parameters ?? [];
            return new Predis_Cluster(new Parameters($options_parameters));
        };
    }
    /**
     * {@inheritdoc}
     */
    public function get_default(Options_Interface $options)
    {
        return $this->get_connection_initializer($options, $this->get_default_connection_initializer());
    }
}