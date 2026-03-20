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
namespace Predis\Command\Processor;

use InvalidArgumentException;
use Predis\Command\Command_Interface;
use Predis\Command\Prefixable_Command_Interface;
/**
 * Command processor capable of prefixing keys stored in the arguments of Redis
 * commands supported.
 */
class Key_Prefix_Processor implements Processor_Interface
{
    private $prefix;
    private $commands;
    /**
     * @param string $prefix Prefix for the keys.
     */
    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }
    /**
     * Sets a prefix that is applied to all the keys.
     *
     * @param string $prefix Prefix for the keys.
     */
    public function set_prefix($prefix): void
    {
        $this->prefix = $prefix;
    }
    /**
     * Gets the current prefix.
     *
     * @return string
     */
    public function get_prefix()
    {
        return $this->prefix;
    }
    /**
     * {@inheritdoc}
     */
    public function process(Command_Interface $command): void
    {
        if ($command instanceof Prefixable_Command_Interface) {
            $command->prefix_keys($this->prefix);
        } elseif (isset($this->commands[$command_id = strtoupper($command->get_id())])) {
            $this->commands[$command_id]($command, $this->prefix);
        }
    }
    /**
     * Sets an handler for the specified command ID.
     *
     * The callback signature must have 2 parameters of the following types:
     *
     *   - Predis\Command\CommandInterface (command instance)
     *   - String (prefix)
     *
     * When the callback argument is omitted or NULL, the previously
     * associated handler for the specified command ID is removed.
     *
     * @param string $commandID The ID of the command to be handled.
     * @param mixed  $callback  A valid callable object or NULL.
     *
     * @throws InvalidArgumentException
     */
    public function set_command_handler($command_id, $callback = null): void
    {
        $command_id = strtoupper($command_id);
        if (!isset($callback)) {
            unset($this->commands[$command_id]);
            return;
        }
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Callback must be a valid callable object or NULL');
        }
        $this->commands[$command_id] = $callback;
    }
    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->get_prefix();
    }
}