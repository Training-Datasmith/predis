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
namespace Predis\Configuration;

use Predis\Command\Processor\Processor_Interface;
use Predis\Connection\Factory_Interface;
use Predis\Connection\Parameters_Interface;
/**
 * @property callable                  $aggregate       Custom aggregate connection initializer
 * @property callable                  $cluster         Aggregate connection initializer for clustering
 * @property FactoryInterface          $connections     Connection factory for creating new connections
 * @property bool                      $exceptions      Toggles exceptions in client for -ERR responses
 * @property ProcessorInterface        $prefix          Key prefixing strategy using the supplied string as prefix
 * @property FactoryInterface          $commands        Command factory for creating Redis commands
 * @property array|ParametersInterface $parameters      Parameters associated with connection.
 * @property callable                  $replication     Aggregate connection initializer for replication
 * @property int                       $readTimeout     Timeout in milliseconds between read operations on reading from multiple connections.
 * @property string|string[]           $upstream_driver Upstream driver info for CLIENT SETINFO.
 */
interface Options_Interface
{
    /**
     * Returns the default value for the given option.
     *
     * @param string $option Name of the option
     *
     * @return mixed|null
     */
    public function get_default($option);
    /**
     * Checks if the given option has been set by the user upon initialization.
     *
     * @param string $option Name of the option
     *
     * @return bool
     */
    public function defined($option);
    /**
     * Checks if the given option has been set and does not evaluate to NULL.
     *
     * @param string $option Name of the option
     *
     * @return bool
     */
    public function __isset($option);
    /**
     * Returns the value of the given option.
     *
     * @param string $option Name of the option
     *
     * @return mixed|null
     */
    public function __get($option);
    /**
     * Set the value of the given option.
     *
     * @param string $option Name of the option
     * @param mixed  $value  option value
     *
     * @return void
     */
    public function __set($option, $value);
}