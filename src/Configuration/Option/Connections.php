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
use Predis\Connection\Factory;
use Predis\Connection\Factory_Interface;
use Predis\Connection\Relay_Connection;
use Predis\Connection\Relay_Factory;
/**
 * Configures a new connection factory instance.
 *
 * The client uses the connection factory to create the underlying connections
 * to single redis nodes in a single-server configuration or in replication and
 * cluster configurations.
 */
class Connections implements Option_Interface
{
    /**
     * {@inheritdoc}
     */
    public function filter(Options_Interface $options, $value)
    {
        if (is_callable($value)) {
            $value = call_user_func($value, $options);
        }
        if ($value instanceof Factory_Interface) {
            return $value;
        }
        if (is_array($value)) {
            return $this->create_factory_by_array($options, $value);
        }
        if (is_string($value)) {
            return $this->create_factory_by_string($options, $value);
        }
        throw new InvalidArgumentException(sprintf('%s expects a valid connection factory', static::class));
    }
    /**
     * Creates a new connection factory from a named array.
     *
     * The factory instance is configured according to the supplied named array
     * mapping URI schemes (passed as keys) to the FCQN of classes implementing
     * Predis\Connection\NodeConnectionInterface, or callable objects acting as
     * lazy initializers and returning new instances of classes implementing
     * Predis\Connection\NodeConnectionInterface.
     *
     * @param OptionsInterface $options Client options
     * @param array            $value   Named array mapping URI schemes to classes or callables
     *
     * @return FactoryInterface
     */
    protected function create_factory_by_array(Options_Interface $options, array $value)
    {
        /**
         * @var FactoryInterface
         */
        $factory = $this->get_default($options);
        foreach ($value as $scheme => $initializer) {
            $factory->define($scheme, $initializer);
        }
        return $factory;
    }
    /**
     * Creates a new connection factory from a descriptive string.
     *
     * The factory instance is configured according to the supplied descriptive
     * string that identifies specific configurations of schemes and connection
     * classes. Supported configuration values are:
     *
     * - "relay" maps tcp, redis, unix, tls, rediss to RelayConnection
     *
     * @param OptionsInterface $options Client options
     * @param string           $value   Descriptive string identifying the desired configuration
     *
     * @return FactoryInterface
     */
    protected function create_factory_by_string(Options_Interface $options, string $value)
    {
        switch (strtolower($value)) {
            case 'relay':
                return $this->get_relay_factory($options);
            case 'default':
                return $this->get_default($options);
            default:
                throw new InvalidArgumentException(sprintf('%s does not recognize `%s` as a supported configuration string', static::class, $value));
        }
    }
    /**
     * {@inheritdoc}
     */
    public function get_default(Options_Interface $options): \Predis\Connection\Factory
    {
        $factory = new Factory();
        if ($options->defined('parameters')) {
            $factory->set_default_parameters($options->parameters);
        }
        if ($options->defined('upstream_driver')) {
            $factory->set_upstream_driver($options->upstream_driver);
        }
        return $factory;
    }
    /**
     * Creates RelayFactory instance.
     */
    private function get_relay_factory(Options_Interface $options): Factory_Interface
    {
        $factory = new Relay_Factory();
        if ($options->defined('parameters')) {
            $factory->set_default_parameters($options->parameters);
        }
        if ($options->defined('upstream_driver')) {
            $factory->set_upstream_driver($options->upstream_driver);
        }
        return $factory;
    }
}