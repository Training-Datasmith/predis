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
use Predis\Command\Factory_Interface;
use Predis\Command\Raw_Factory;
use Predis\Command\Redis_Factory;
use Predis\Configuration\Option_Interface;
use Predis\Configuration\Options_Interface;
/**
 * Configures a connection factory to be used by the client.
 */
class Commands implements Option_Interface
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
        throw new InvalidArgumentException(sprintf('%s expects a valid command factory', static::class));
    }
    /**
     * Creates a new default command factory from a named array.
     *
     * The factory instance is configured according to the supplied named array
     * mapping command IDs (passed as keys) to the FCQN of classes implementing
     * Predis\Command\CommandInterface.
     *
     * @param OptionsInterface $options Client options container
     * @param array            $value   Named array mapping command IDs to classes
     *
     * @return FactoryInterface
     */
    protected function create_factory_by_array(Options_Interface $options, array $value)
    {
        /**
         * @var FactoryInterface
         */
        $commands = $this->get_default($options);
        foreach ($value as $command_id => $command_class) {
            if ($command_class === null) {
                $commands->undefine($command_id);
            } else {
                $commands->define($command_id, $command_class);
            }
        }
        return $commands;
    }
    /**
     * Creates a new command factory from a descriptive string.
     *
     * The factory instance is configured according to the supplied descriptive
     * string that identifies specific configurations of schemes and connection
     * classes. Supported configuration values are:
     *
     * - "predis" returns the default command factory used by Predis
     * - "raw" returns a command factory that creates only raw commands
     * - "default" is simply an alias of "predis"
     *
     * @param OptionsInterface $options Client options container
     * @param string           $value   Descriptive string identifying the desired configuration
     *
     * @return FactoryInterface
     */
    protected function create_factory_by_string(Options_Interface $options, string $value)
    {
        switch (strtolower($value)) {
            case 'default':
            case 'predis':
                return $this->get_default($options);
            case 'raw':
                return $this->create_raw_factory($options);
            default:
                throw new InvalidArgumentException(sprintf('%s does not recognize `%s` as a supported configuration string', static::class, $value));
        }
    }
    /**
     * Creates a new raw command factory instance.
     *
     * @param OptionsInterface $options Client options container
     */
    protected function create_raw_factory(Options_Interface $options): Factory_Interface
    {
        $commands = new Raw_Factory();
        if (isset($options->prefix)) {
            throw new InvalidArgumentException(sprintf('%s does not support key prefixing', Raw_Factory::class));
        }
        return $commands;
    }
    /**
     * {@inheritdoc}
     */
    public function get_default(Options_Interface $options): \Predis\Command\Redis_Factory
    {
        $commands = new Redis_Factory();
        if (isset($options->prefix)) {
            $commands->set_processor($options->prefix);
        }
        return $commands;
    }
}