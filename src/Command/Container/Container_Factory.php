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
namespace Predis\Command\Container;

use Predis\Client_Configuration;
use Predis\Client_Interface;
use UnexpectedValueException;
class Container_Factory
{
    private const CONTAINER_NAMESPACE = "Predis\\Command\\Container";
    /**
     * Mappings for class names that corresponds to PHP reserved words.
     *
     * @var array
     */
    private static $special_mappings = ['FUNCTION' => FUNCTIONS::class];
    /**
     * Creates container command.
     */
    public static function create(Client_Interface $client, string $container_command_id): Container_Interface
    {
        $container_command_id = strtoupper($container_command_id);
        $command_module = self::resolve_command_module_by_prefix($container_command_id);
        if (null !== $command_module) {
            if (class_exists($container_class = self::CONTAINER_NAMESPACE . '\\' . $command_module . '\\' . $container_command_id)) {
                return new $container_class($client);
            }
            throw new UnexpectedValueException("Given module container command '{$container_command_id}' is not supported.");
        }
        if (class_exists($container_class = self::CONTAINER_NAMESPACE . '\\' . $container_command_id)) {
            return new $container_class($client);
        }
        if (array_key_exists($container_command_id, self::$special_mappings)) {
            $container_class = self::$special_mappings[$container_command_id];
            return new $container_class($client);
        }
        throw new UnexpectedValueException("Given container command '{$container_command_id}' is not supported.");
    }
    private static function resolve_command_module_by_prefix(string $command_id): ?string
    {
        $modules = Client_Configuration::get_modules();
        foreach ($modules as $module) {
            if (preg_match("/^{$module['commandPrefix']}/", $command_id)) {
                return $module['name'];
            }
        }
        return null;
    }
}