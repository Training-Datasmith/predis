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
namespace Predis\Replication;

use Predis\Command\Command_Interface;
use Predis\Not_Supported_Exception;
/**
 * Defines a strategy for master/slave replication.
 */
class Replication_Strategy
{
    protected $disallowed;
    protected $readonly;
    protected $readonly_sha1;
    protected $load_balancing = true;
    public function __construct()
    {
        $this->disallowed = $this->get_disallowed_operations();
        $this->readonly = $this->get_read_only_operations();
        $this->readonly_sha1 = [];
    }
    /**
     * Returns if the specified command will perform a read-only operation
     * on Redis or not.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return bool
     * @throws NotSupportedException
     */
    public function is_read_operation(Command_Interface $command)
    {
        if (!$this->load_balancing) {
            return false;
        }
        if (isset($this->disallowed[$id = $command->get_id()])) {
            throw new Not_Supported_Exception("The command '{$id}' is not allowed in replication mode.");
        }
        if (isset($this->readonly[$id])) {
            if (true === $readonly = $this->readonly[$id]) {
                return true;
            }
            return call_user_func($readonly, $command);
        }
        if (($eval = $id === 'EVAL') || $id === 'EVALSHA') {
            $argument = $command->get_argument(0);
            $sha1 = $eval ? sha1(strval($argument)) : $argument;
            if (isset($this->readonly_sha1[$sha1])) {
                if (true === $readonly = $this->readonly_sha1[$sha1]) {
                    return true;
                }
                return call_user_func($readonly, $command);
            }
        }
        return false;
    }
    /**
     * Returns if the specified command is not allowed for execution in a master
     * / slave replication context.
     *
     * @param CommandInterface $command Command instance.
     */
    public function is_disallowed_operation(Command_Interface $command): bool
    {
        return isset($this->disallowed[$command->get_id()]);
    }
    /**
     * Checks if BITFIELD performs a read-only operation by looking for certain
     * SET and INCRYBY modifiers in the arguments array of the command.
     *
     * @param CommandInterface $command Command instance.
     */
    protected function is_bitfield_read_only(Command_Interface $command): bool
    {
        $arguments = $command->get_arguments();
        $argc = count($arguments);
        if ($argc >= 2) {
            for ($i = 1; $i < $argc; ++$i) {
                $argument = strtoupper($arguments[$i]);
                if ($argument === 'SET' || $argument === 'INCRBY') {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * Checks if a GEORADIUS command is a readable operation by parsing the
     * arguments array of the specified command instance.
     *
     * @param CommandInterface $command Command instance.
     */
    protected function is_georadius_read_only(Command_Interface $command): bool
    {
        $arguments = $command->get_arguments();
        $argc = count($arguments);
        $start_index = $command->get_id() === 'GEORADIUS' ? 5 : 4;
        if ($argc > $start_index) {
            for ($i = $start_index; $i < $argc; ++$i) {
                $argument = strtoupper($arguments[$i]);
                if ($argument === 'STORE' || $argument === 'STOREDIST') {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * Marks a command as a read-only operation.
     *
     * When the behavior of a command can be decided only at runtime depending
     * on its arguments, a callable object can be provided to dynamically check
     * if the specified command performs a read or a write operation.
     *
     * @param string $commandID Command ID.
     * @param mixed  $readonly  A boolean value or a callable object.
     */
    public function set_command_read_only($command_id, $readonly = true): void
    {
        $command_id = strtoupper($command_id);
        if ($readonly) {
            $this->readonly[$command_id] = $readonly;
        } else {
            unset($this->readonly[$command_id]);
        }
    }
    /**
     * Marks a Lua script for EVAL and EVALSHA as a read-only operation. When
     * the behaviour of a script can be decided only at runtime depending on
     * its arguments, a callable object can be provided to dynamically check
     * if the passed instance of EVAL or EVALSHA performs write operations or
     * not.
     *
     * @param string $script   Body of the Lua script.
     * @param mixed  $readonly A boolean value or a callable object.
     */
    public function set_script_read_only($script, $readonly = true): void
    {
        $sha1 = sha1($script);
        if ($readonly) {
            $this->readonly_sha1[$sha1] = $readonly;
        } else {
            unset($this->readonly_sha1[$sha1]);
        }
    }
    /**
     * Returns the default list of disallowed commands.
     */
    protected function get_disallowed_operations(): array
    {
        return ['SHUTDOWN' => true, 'INFO' => true, 'DBSIZE' => true, 'LASTSAVE' => true, 'CONFIG' => true, 'MONITOR' => true, 'SLAVEOF' => true, 'SAVE' => true, 'BGSAVE' => true, 'BGREWRITEAOF' => true, 'SLOWLOG' => true];
    }
    /**
     * Returns the default list of commands performing read-only operations.
     */
    protected function get_read_only_operations(): array
    {
        return ['EXISTS' => true, 'TYPE' => true, 'KEYS' => true, 'SCAN' => true, 'RANDOMKEY' => true, 'TTL' => true, 'GET' => true, 'MGET' => true, 'SUBSTR' => true, 'STRLEN' => true, 'GETRANGE' => true, 'GETBIT' => true, 'LLEN' => true, 'LRANGE' => true, 'LINDEX' => true, 'SCARD' => true, 'SISMEMBER' => true, 'SINTER' => true, 'SUNION' => true, 'SDIFF' => true, 'SMEMBERS' => true, 'SSCAN' => true, 'SRANDMEMBER' => true, 'ZRANGE' => true, 'ZREVRANGE' => true, 'ZRANGEBYSCORE' => true, 'ZREVRANGEBYSCORE' => true, 'ZCARD' => true, 'ZSCORE' => true, 'ZCOUNT' => true, 'ZRANK' => true, 'ZREVRANK' => true, 'ZSCAN' => true, 'ZLEXCOUNT' => true, 'ZRANGEBYLEX' => true, 'ZREVRANGEBYLEX' => true, 'HGET' => true, 'HMGET' => true, 'HEXISTS' => true, 'HLEN' => true, 'HKEYS' => true, 'HVALS' => true, 'HGETALL' => true, 'HSCAN' => true, 'HSTRLEN' => true, 'PING' => true, 'AUTH' => true, 'SELECT' => true, 'ECHO' => true, 'QUIT' => true, 'OBJECT' => true, 'BITCOUNT' => true, 'BITPOS' => true, 'TIME' => true, 'PFCOUNT' => true, 'BITFIELD' => [$this, 'isBitfieldReadOnly'], 'GEOHASH' => true, 'GEOPOS' => true, 'GEODIST' => true, 'GEORADIUS' => [$this, 'isGeoradiusReadOnly'], 'GEORADIUSBYMEMBER' => [$this, 'isGeoradiusReadOnly'], 'GEOSEARCH' => true];
    }
    /**
     * Disables reads to slaves when using
     * a replication topology.
     */
    public function disable_load_balancing(): self
    {
        $this->load_balancing = false;
        return $this;
    }
}