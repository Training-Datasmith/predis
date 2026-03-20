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

/**
 * Base class used to implement an higher level abstraction for commands based
 * on Lua scripting with EVAL and EVALSHA.
 *
 * @see http://redis.io/commands/eval
 */
abstract class Script_Command extends Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id()
    {
        return 'EVALSHA';
    }
    /**
     * Gets the body of a Lua script.
     *
     * @return string
     */
    abstract public function get_script();
    /**
     * Calculates the SHA1 hash of the body of the script.
     *
     * @return string SHA1 hash.
     */
    public function get_script_hash()
    {
        return sha1($this->get_script());
    }
    /**
     * Specifies the number of arguments that should be considered as keys.
     *
     * The default behaviour for the base class is to return 0 to indicate that
     * all the elements of the arguments array should be considered as keys, but
     * subclasses can enforce a static number of keys.
     *
     * @return int
     */
    protected function get_keys_count()
    {
        return 0;
    }
    /**
     * Returns the elements from the arguments that are identified as keys.
     *
     * @return array
     */
    public function get_keys()
    {
        return array_slice($this->get_arguments(), 2, $this->get_keys_count());
    }
    /**
     * {@inheritdoc}
     */
    public function set_arguments(array $arguments): void
    {
        if (($numkeys = $this->get_keys_count()) && $numkeys < 0) {
            $numkeys = count($arguments) + $numkeys;
        }
        $arguments = array_merge([$this->get_script_hash(), (int) $numkeys], $arguments);
        parent::set_arguments($arguments);
    }
    /**
     * Returns arguments for EVAL command.
     *
     * @return array
     */
    public function get_eval_arguments()
    {
        $arguments = $this->get_arguments();
        $arguments[0] = $this->get_script();
        return $arguments;
    }
    /**
     * Returns the equivalent EVAL command as a raw command instance.
     *
     * @return RawCommand
     */
    public function get_eval_command()
    {
        return new Raw_Command('EVAL', $this->get_eval_arguments());
    }
}