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

use InvalidArgumentException;
use Predis\Client_Exception;
use Predis\Command\Processor\Processor_Interface;
/**
 * Base command factory class.
 *
 * This class provides all of the common functionalities required for a command
 * factory to create new instances of Redis commands objects. It also allows to
 * define or undefine command handler classes for each command ID.
 */
abstract class Factory implements Factory_Interface
{
    protected $commands = [];
    protected $processor;
    /**
     * {@inheritdoc}
     */
    public function supports(string ...$command_i_ds): bool
    {
        foreach ($command_i_ds as $command_id) {
            if ($this->get_command_class($command_id) === null) {
                return false;
            }
        }
        return true;
    }
    /**
     * Returns the FQCN of a class that represents the specified command ID.
     *
     * @codeCoverageIgnore
     *
     * @param string $commandID Command ID
     */
    public function get_command_class(string $command_id): ?string
    {
        return $this->commands[strtoupper($command_id)] ?? null;
    }
    /**
     * {@inheritdoc}
     */
    public function create(string $command_id, array $arguments = []): Command_Interface
    {
        if (!$command_class = $this->get_command_class($command_id)) {
            $command_id = strtoupper($command_id);
            throw new Client_Exception("Command `{$command_id}` is not a registered Redis command.");
        }
        $command = new $command_class();
        $command->set_arguments($arguments);
        if (isset($this->processor)) {
            $this->processor->process($command);
        }
        return $command;
    }
    /**
     * Defines a command in the factory.
     *
     * Only classes implementing Predis\Command\CommandInterface are allowed to
     * handle a command. If the command specified by its ID is already handled
     * by the factory, the underlying command class is replaced by the new one.
     *
     * @param string $commandID    Command ID
     * @param string $commandClass FQCN of a class implementing Predis\Command\CommandInterface
     *
     * @throws InvalidArgumentException
     */
    public function define(string $command_id, string $command_class): void
    {
        if (!is_a($command_class, 'Predis\Command\CommandInterface', true)) {
            throw new InvalidArgumentException("Class {$command_class} must implement Predis\\Command\\CommandInterface");
        }
        $this->commands[strtoupper($command_id)] = $command_class;
    }
    /**
     * Undefines a command in the factory.
     *
     * When the factory already has a class handler associated to the specified
     * command ID it is removed from the map of known commands. Nothing happens
     * when the command is not handled by the factory.
     *
     * @param string $commandID Command ID
     */
    public function undefine(string $command_id): void
    {
        unset($this->commands[strtoupper($command_id)]);
    }
    /**
     * Sets a command processor for processing command arguments.
     *
     * Command processors are used to process and transform arguments of Redis
     * commands before their newly created instances are returned to the caller
     * of "create()".
     *
     * A NULL value can be used to effectively unset any processor if previously
     * set for the command factory.
     *
     * @param ProcessorInterface|null $processor Command processor or NULL value.
     */
    public function set_processor(?Processor_Interface $processor): void
    {
        $this->processor = $processor;
    }
    /**
     * Returns the current command processor.
     */
    public function get_processor(): ?Processor_Interface
    {
        return $this->processor;
    }
}