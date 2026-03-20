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
namespace Predis\Command;

use Predis\Client_Configuration;
use Predis\Command\Redis\FUNCTIONS;
/**
 * Command factory for mainline Redis servers.
 *
 * This factory is intended to handle standard commands implemented by mainline
 * Redis servers. By default it maps a command ID to a specific command handler
 * class in the Predis\Command\Redis namespace but this can be overridden for
 * any command ID simply by defining a new command handler class implementing
 * Predis\Command\CommandInterface.
 */
class Redis_Factory extends Factory
{
    private const COMMANDS_NAMESPACE = "Predis\\Command\\Redis";
    public function __construct()
    {
        $this->commands = [
            'ECHO' => \Predis\Command\Redis\ECHO_::class,
            'EVAL' => \Predis\Command\Redis\EVAL_::class,
            'OBJECT' => \Predis\Command\Redis\OBJECT_::class,
            // Class name corresponds to PHP reserved word "function", added mapping to bypass restrictions
            'FUNCTION' => FUNCTIONS::class,
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function get_command_class(string $command_id): ?string
    {
        $command_id = strtoupper($command_id);
        if (isset($this->commands[$command_id]) || array_key_exists($command_id, $this->commands)) {
            return $this->commands[$command_id];
        }
        $command_class = $this->resolve($command_id);
        if (null === $command_class) {
            return null;
        }
        $this->commands[$command_id] = $command_class;
        return $command_class;
    }
    /**
     * {@inheritdoc}
     */
    public function undefine(string $command_id): void
    {
        // NOTE: we explicitly associate `NULL` to the command ID in the map
        // instead of the parent's `unset()` because our subclass tries to load
        // a predefined class from the Predis\Command\Redis namespace when no
        // explicit mapping is defined, see RedisFactory::getCommandClass() for
        // details of the implementation of this mechanism.
        $this->commands[strtoupper($command_id)] = null;
    }
    /**
     * Resolves command object from given command ID.
     *
     * @param  string      $commandID Command ID of virtual method call
     * @return string|null FQDN of corresponding command object
     */
    private function resolve(string $command_id): ?string
    {
        if (class_exists($command_class = self::COMMANDS_NAMESPACE . '\\' . $command_id)) {
            return $command_class;
        }
        $command_module = $this->resolve_command_module_by_prefix($command_id);
        if (null === $command_module) {
            return null;
        }
        if (class_exists($command_class = self::COMMANDS_NAMESPACE . '\\' . $command_module . '\\' . $command_id)) {
            return $command_class;
        }
        return null;
    }
    private function resolve_command_module_by_prefix(string $command_id): ?string
    {
        foreach (Client_Configuration::get_modules() as $module) {
            if (preg_match("/^{$module['commandPrefix']}/", $command_id)) {
                return $module['name'];
            }
        }
        return null;
    }
}