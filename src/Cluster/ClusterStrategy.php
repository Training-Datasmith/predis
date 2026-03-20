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
namespace Predis\Cluster;

use InvalidArgumentException;
use Predis\Command\Command_Interface;
use Predis\Command\Script_Command;
/**
 * Common class implementing the logic needed to support clustering strategies.
 */
abstract class Cluster_Strategy implements Strategy_Interface
{
    protected $commands;
    public function __construct()
    {
        $this->commands = $this->get_default_commands();
    }
    /**
     * Returns the default map of supported commands with their handlers.
     *
     * @return array
     */
    protected function get_default_commands()
    {
        $get_key_from_first_argument = [$this, 'getKeyFromFirstArgument'];
        $get_key_from_all_arguments = [$this, 'getKeyFromAllArguments'];
        return [
            /* commands operating on the key space */
            'EXISTS' => $get_key_from_all_arguments,
            'DEL' => $get_key_from_all_arguments,
            'TYPE' => $get_key_from_first_argument,
            'EXPIRE' => $get_key_from_first_argument,
            'EXPIREAT' => $get_key_from_first_argument,
            'PERSIST' => $get_key_from_first_argument,
            'PEXPIRE' => $get_key_from_first_argument,
            'PEXPIREAT' => $get_key_from_first_argument,
            'TTL' => $get_key_from_first_argument,
            'PTTL' => $get_key_from_first_argument,
            'SORT' => [$this, 'getKeyFromSortCommand'],
            'DUMP' => $get_key_from_first_argument,
            'RESTORE' => $get_key_from_first_argument,
            'FLUSHDB' => [$this, 'getFakeKey'],
            /* commands operating on string values */
            'APPEND' => $get_key_from_first_argument,
            'DECR' => $get_key_from_first_argument,
            'DECRBY' => $get_key_from_first_argument,
            'GET' => $get_key_from_first_argument,
            'GETBIT' => $get_key_from_first_argument,
            'MGET' => $get_key_from_all_arguments,
            'SET' => $get_key_from_first_argument,
            'GETRANGE' => $get_key_from_first_argument,
            'GETSET' => $get_key_from_first_argument,
            'INCR' => $get_key_from_first_argument,
            'INCRBY' => $get_key_from_first_argument,
            'INCRBYFLOAT' => $get_key_from_first_argument,
            'SETBIT' => $get_key_from_first_argument,
            'SETEX' => $get_key_from_first_argument,
            'MSET' => [$this, 'getKeyFromInterleavedArguments'],
            'MSETNX' => [$this, 'getKeyFromInterleavedArguments'],
            'SETNX' => $get_key_from_first_argument,
            'SETRANGE' => $get_key_from_first_argument,
            'STRLEN' => $get_key_from_first_argument,
            'SUBSTR' => $get_key_from_first_argument,
            'BITOP' => [$this, 'getKeyFromBitOp'],
            'BITCOUNT' => $get_key_from_first_argument,
            'BITFIELD' => $get_key_from_first_argument,
            /* commands operating on lists */
            'LINSERT' => $get_key_from_first_argument,
            'LINDEX' => $get_key_from_first_argument,
            'LLEN' => $get_key_from_first_argument,
            'LPOP' => $get_key_from_first_argument,
            'RPOP' => $get_key_from_first_argument,
            'RPOPLPUSH' => $get_key_from_all_arguments,
            'BLPOP' => [$this, 'getKeyFromBlockingListCommands'],
            'BRPOP' => [$this, 'getKeyFromBlockingListCommands'],
            'BRPOPLPUSH' => [$this, 'getKeyFromBlockingListCommands'],
            'LPUSH' => $get_key_from_first_argument,
            'LPUSHX' => $get_key_from_first_argument,
            'RPUSH' => $get_key_from_first_argument,
            'RPUSHX' => $get_key_from_first_argument,
            'LRANGE' => $get_key_from_first_argument,
            'LREM' => $get_key_from_first_argument,
            'LSET' => $get_key_from_first_argument,
            'LTRIM' => $get_key_from_first_argument,
            /* commands operating on sets */
            'SADD' => $get_key_from_first_argument,
            'SCARD' => $get_key_from_first_argument,
            'SDIFF' => $get_key_from_all_arguments,
            'SDIFFSTORE' => $get_key_from_all_arguments,
            'SINTER' => $get_key_from_all_arguments,
            'SINTERSTORE' => $get_key_from_all_arguments,
            'SUNION' => $get_key_from_all_arguments,
            'SUNIONSTORE' => $get_key_from_all_arguments,
            'SISMEMBER' => $get_key_from_first_argument,
            'SMEMBERS' => $get_key_from_first_argument,
            'SSCAN' => $get_key_from_first_argument,
            'SPOP' => $get_key_from_first_argument,
            'SRANDMEMBER' => $get_key_from_first_argument,
            'SREM' => $get_key_from_first_argument,
            /* commands operating on sorted sets */
            'ZADD' => $get_key_from_first_argument,
            'ZCARD' => $get_key_from_first_argument,
            'ZCOUNT' => $get_key_from_first_argument,
            'ZINCRBY' => $get_key_from_first_argument,
            'ZINTERSTORE' => [$this, 'getKeyFromZsetAggregationCommands'],
            'ZRANGE' => $get_key_from_first_argument,
            'ZRANGEBYSCORE' => $get_key_from_first_argument,
            'ZRANK' => $get_key_from_first_argument,
            'ZREM' => $get_key_from_first_argument,
            'ZREMRANGEBYRANK' => $get_key_from_first_argument,
            'ZREMRANGEBYSCORE' => $get_key_from_first_argument,
            'ZREVRANGE' => $get_key_from_first_argument,
            'ZREVRANGEBYSCORE' => $get_key_from_first_argument,
            'ZREVRANK' => $get_key_from_first_argument,
            'ZSCORE' => $get_key_from_first_argument,
            'ZUNIONSTORE' => [$this, 'getKeyFromZsetAggregationCommands'],
            'ZSCAN' => $get_key_from_first_argument,
            'ZLEXCOUNT' => $get_key_from_first_argument,
            'ZRANGEBYLEX' => $get_key_from_first_argument,
            'ZREMRANGEBYLEX' => $get_key_from_first_argument,
            'ZREVRANGEBYLEX' => $get_key_from_first_argument,
            /* commands operating on hashes */
            'HDEL' => $get_key_from_first_argument,
            'HEXISTS' => $get_key_from_first_argument,
            'HGET' => $get_key_from_first_argument,
            'HGETALL' => $get_key_from_first_argument,
            'HMGET' => $get_key_from_first_argument,
            'HMSET' => $get_key_from_first_argument,
            'HINCRBY' => $get_key_from_first_argument,
            'HINCRBYFLOAT' => $get_key_from_first_argument,
            'HKEYS' => $get_key_from_first_argument,
            'HLEN' => $get_key_from_first_argument,
            'HSET' => $get_key_from_first_argument,
            'HSETNX' => $get_key_from_first_argument,
            'HVALS' => $get_key_from_first_argument,
            'HSCAN' => $get_key_from_first_argument,
            'HSTRLEN' => $get_key_from_first_argument,
            /* commands operating on streams */
            'XADD' => $get_key_from_first_argument,
            'XDEL' => $get_key_from_first_argument,
            'XRANGE' => $get_key_from_first_argument,
            /* commands operating on HyperLogLog */
            'PFADD' => $get_key_from_first_argument,
            'PFCOUNT' => $get_key_from_all_arguments,
            'PFMERGE' => $get_key_from_all_arguments,
            /* scripting */
            'EVAL' => [$this, 'getKeyFromScriptingCommands'],
            'EVALSHA' => [$this, 'getKeyFromScriptingCommands'],
            'EVAL_RO' => [$this, 'getKeyFromScriptingCommands'],
            'EVALSHA_RO' => [$this, 'getKeyFromScriptingCommands'],
            /* server */
            'INFO' => [$this, 'getFakeKey'],
            /* commands performing geospatial operations */
            'GEOADD' => $get_key_from_first_argument,
            'GEOHASH' => $get_key_from_first_argument,
            'GEOPOS' => $get_key_from_first_argument,
            'GEODIST' => $get_key_from_first_argument,
            'GEORADIUS' => [$this, 'getKeyFromGeoradiusCommands'],
            'GEORADIUSBYMEMBER' => [$this, 'getKeyFromGeoradiusCommands'],
            /* sharded pubsub */
            'SSUBSCRIBE' => $get_key_from_all_arguments,
            'SUNSUBSCRIBE' => [$this, 'getKeyFromSUnsubscribeCommand'],
            'SPUBLISH' => $get_key_from_first_argument,
            /* cluster */
            'CLUSTER' => [$this, 'getFakeKey'],
            /* control */
            'ACL' => [$this, 'getFakeKey'],
        ];
    }
    /**
     * Returns the list of IDs for the supported commands.
     *
     * @return array
     */
    public function get_supported_commands()
    {
        return array_keys($this->commands);
    }
    /**
     * Sets an handler for the specified command ID.
     *
     * The signature of the callback must have a single parameter of type
     * Predis\Command\CommandInterface.
     *
     * When the callback argument is omitted or NULL, the previously associated
     * handler for the specified command ID is removed.
     *
     * @param string $commandID Command ID.
     * @param mixed  $callback  A valid callable object, or NULL to unset the handler.
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
            throw new InvalidArgumentException('The argument must be a callable object or NULL.');
        }
        $this->commands[$command_id] = $callback;
    }
    /**
     * Get fake key for commands with no key argument.
     */
    protected function get_fake_key(): string
    {
        return 'key';
    }
    /**
     * Extracts the key from the first argument of a command instance.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string
     */
    protected function get_key_from_first_argument(Command_Interface $command)
    {
        return $command->get_argument(0);
    }
    /**
     * Extracts the key from a command with multiple keys only when all keys in
     * the arguments array produce the same hash.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string|null
     */
    protected function get_key_from_all_arguments(Command_Interface $command)
    {
        $arguments = $command->get_arguments();
        if (!$this->check_same_slot_for_keys($arguments)) {
            return null;
        }
        return $arguments[0];
    }
    /**
     * Extracts the key from a command with multiple keys only when all keys in
     * the arguments array produce the same hash.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string|null
     */
    protected function get_key_from_interleaved_arguments(Command_Interface $command)
    {
        $arguments = $command->get_arguments();
        $keys = [];
        for ($i = 0; $i < count($arguments); $i += 2) {
            $keys[] = $arguments[$i];
        }
        if (!$this->check_same_slot_for_keys($keys)) {
            return null;
        }
        return $arguments[0];
    }
    /**
     * Extracts the key from SORT command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string|null
     */
    protected function get_key_from_sort_command(Command_Interface $command)
    {
        $arguments = $command->get_arguments();
        $first_key = $arguments[0];
        if (1 === $argc = count($arguments)) {
            return $first_key;
        }
        $keys = [$first_key];
        for ($i = 1; $i < $argc; ++$i) {
            if (strtoupper($arguments[$i]) === 'STORE') {
                $keys[] = $arguments[++$i];
            }
        }
        if (!$this->check_same_slot_for_keys($keys)) {
            return null;
        }
        return $first_key;
    }
    /**
     * Extracts the key from BLPOP and BRPOP commands.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string|null
     */
    protected function get_key_from_blocking_list_commands(Command_Interface $command)
    {
        $arguments = $command->get_arguments();
        if (!$this->check_same_slot_for_keys(array_slice($arguments, 0, count($arguments) - 1))) {
            return null;
        }
        return $arguments[0];
    }
    /**
     * Extracts the key from BITOP command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string|null
     */
    protected function get_key_from_bit_op(Command_Interface $command)
    {
        $arguments = $command->get_arguments();
        if (!$this->check_same_slot_for_keys(array_slice($arguments, 1, count($arguments)))) {
            return null;
        }
        return $arguments[1];
    }
    /**
     * Extracts the key from GEORADIUS and GEORADIUSBYMEMBER commands.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string|null
     */
    protected function get_key_from_georadius_commands(Command_Interface $command)
    {
        $arguments = $command->get_arguments();
        $argc = count($arguments);
        $start_index = $command->get_id() === 'GEORADIUS' ? 5 : 4;
        if ($argc > $start_index) {
            $keys = [$arguments[0]];
            for ($i = $start_index; $i < $argc; ++$i) {
                $argument = strtoupper($arguments[$i]);
                if ($argument === 'STORE' || $argument === 'STOREDIST') {
                    $keys[] = $arguments[++$i];
                }
            }
            if (!$this->check_same_slot_for_keys($keys)) {
                return null;
            }
        }
        return $arguments[0];
    }
    /**
     * Extracts the key from ZINTERSTORE and ZUNIONSTORE commands.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string|null
     */
    protected function get_key_from_zset_aggregation_commands(Command_Interface $command)
    {
        $arguments = $command->get_arguments();
        $keys = array_merge([$arguments[0]], array_slice($arguments, 2, $arguments[1]));
        if (!$this->check_same_slot_for_keys($keys)) {
            return null;
        }
        return $arguments[0];
    }
    /**
     * Extracts key from SUNSUBSCRIBE command if it's given.
     *
     * @return string
     */
    protected function get_key_from_s_unsubscribe_command(Command_Interface $command): ?string
    {
        $arguments = $command->get_arguments();
        // SUNSUBSCRIBE command could be called without arguments, so it doesn't matter on each node it will be called.
        if (empty($arguments)) {
            return 'fake';
        }
        return $this->get_key_from_all_arguments($command);
    }
    /**
     * Extracts the key from EVAL and EVALSHA commands.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string|null
     */
    protected function get_key_from_scripting_commands(Command_Interface $command)
    {
        $keys = $command instanceof Script_Command ? $command->get_keys() : array_slice($args = $command->get_arguments(), 2, $args[1]);
        if (!$keys || !$this->check_same_slot_for_keys($keys)) {
            return null;
        }
        return $keys[0];
    }
    /**
     * {@inheritdoc}
     */
    public function get_slot(Command_Interface $command)
    {
        $slot = $command->get_slot();
        if (!isset($slot) && isset($this->commands[$cmd_id = $command->get_id()])) {
            $key = call_user_func($this->commands[$cmd_id], $command);
            if (isset($key)) {
                $slot = $this->get_slot_by_key($key);
                $command->set_slot($slot);
            }
        }
        return $slot;
    }
    /**
     * {@inheritdoc}
     */
    public function check_same_slot_for_keys(array $keys): bool
    {
        if (!$count = count($keys)) {
            return false;
        }
        $current_slot = $this->get_slot_by_key($keys[0]);
        for ($i = 1; $i < $count; ++$i) {
            $next_slot = $this->get_slot_by_key($keys[$i]);
            if ($current_slot !== $next_slot) {
                return false;
            }
        }
        return true;
    }
    /**
     * Returns only the hashable part of a key (delimited by "{...}"), or the
     * whole key if a key tag is not found in the string.
     *
     * @param string $key A key.
     *
     * @return string
     */
    protected function extract_key_tag($key)
    {
        if (false !== $start = strpos($key, '{')) {
            if (false !== ($end = strpos($key, '}', $start)) && $end !== ++$start) {
                $key = substr($key, $start, $end - $start);
            }
        }
        return $key;
    }
}