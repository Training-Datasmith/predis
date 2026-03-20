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
use UnexpectedValueException;
/**
 * Base class for Redis commands.
 */
abstract class Command implements Command_Interface
{
    private $slot;
    private $arguments = [];
    /**
     * {@inheritdoc}
     */
    public function set_arguments(array $arguments): void
    {
        $this->arguments = $arguments;
        unset($this->slot);
    }
    /**
     * {@inheritdoc}
     */
    public function set_raw_arguments(array $arguments): void
    {
        $this->arguments = $arguments;
        unset($this->slot);
    }
    /**
     * {@inheritdoc}
     */
    public function get_arguments()
    {
        return $this->arguments;
    }
    /**
     * {@inheritdoc}
     */
    public function get_argument($index)
    {
        if (isset($this->arguments[$index])) {
            return $this->arguments[$index];
        }
    }
    /**
     * {@inheritdoc}
     */
    public function set_slot($slot): void
    {
        $this->slot = $slot;
    }
    /**
     * {@inheritdoc}
     */
    public function get_slot()
    {
        return $this->slot ?? null;
    }
    /**
     * {@inheritdoc}
     */
    public function parse_response($data)
    {
        return $data;
    }
    /**
     * {@inheritdoc}
     */
    public function parse_resp3response($data)
    {
        return $data;
    }
    /**
     * Normalizes the arguments array passed to a Redis command.
     *
     * @param array $arguments Arguments for a command.
     *
     * @return array
     */
    public static function normalize_arguments(array $arguments)
    {
        if (count($arguments) === 1 && isset($arguments[0]) && is_array($arguments[0])) {
            return $arguments[0];
        }
        return $arguments;
    }
    /**
     * Normalizes the arguments array passed to a variadic Redis command.
     *
     * @param array $arguments Arguments for a command.
     *
     * @return array
     */
    public static function normalize_variadic(array $arguments)
    {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            return array_merge([$arguments[0]], $arguments[1]);
        }
        return $arguments;
    }
    /**
     * Remove all false values from arguments.
     */
    public function filter_arguments(): void
    {
        $this->arguments = array_filter($this->arguments, static function ($argument): bool {
            return $argument !== false && $argument !== null;
        });
    }
    /**
     * {@inheritDoc}
     */
    public function serialize_command(): string
    {
        $command_id = $this->get_id();
        $arguments = $this->get_arguments();
        $cmdlen = strlen($command_id);
        $reqlen = count($arguments) + 1;
        $buffer = "*{$reqlen}\r\n\${$cmdlen}\r\n{$command_id}\r\n";
        foreach ($arguments as $argument) {
            $arglen = strlen(strval($argument));
            $buffer .= "\${$arglen}\r\n{$argument}\r\n";
        }
        return $buffer;
    }
    /**
     * {@inheritDoc}
     */
    public static function deserialize_command(string $serialized_command): Command_Interface
    {
        if ($serialized_command[0] !== '*') {
            throw new UnexpectedValueException('Invalid serializing format');
        }
        $command_array = explode("\r\n", $serialized_command);
        $command_id = $command_array[2];
        $class_path = __NAMESPACE__ . '\Redis\\';
        // Check if given command is a module command.
        if (count($command_id_array = explode('.', $command_id)) > 1) {
            // Fetch module configuration to resolve namespace.
            $module_configuration = array_filter(Client_Configuration::get_modules(), static function (array $module) use ($command_id_array): bool {
                return $module['commandPrefix'] === $command_id_array[0];
            });
            $command_class = strtoupper($command_id_array[0] . $command_id_array[1]);
            $class_path .= array_shift($module_configuration)['name'] . '\\' . $command_class;
        } else {
            $class_path .= $command_id_array[0];
        }
        $command = new $class_path();
        $arguments = [];
        for ($i = 4, $i_max = count($command_array); $i < $i_max; $i++) {
            $arguments[] = $command_array[$i];
            ++$i;
        }
        $command->set_arguments($arguments);
        return $command;
    }
}