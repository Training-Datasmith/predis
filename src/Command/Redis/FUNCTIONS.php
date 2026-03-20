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
namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
/**
 * @see https://redis.io/commands/?name=function
 *
 * Container command corresponds to any FUNCTION *.
 * Represents any FUNCTION command with subcommand as first argument.
 */
class FUNCTIONS extends Redis_Command
{
    public function get_id(): string
    {
        return 'FUNCTION';
    }
    public function set_arguments(array $arguments): void
    {
        switch ($arguments[0]) {
            case 'FLUSH':
                $this->set_flush_arguments($arguments);
                break;
            case 'LIST':
                $this->set_list_arguments($arguments);
                break;
            case 'LOAD':
                $this->set_load_arguments($arguments);
                break;
            case 'RESTORE':
                $this->set_restore_arguments($arguments);
                break;
            default:
                parent::set_arguments($arguments);
        }
        $this->filter_arguments();
    }
    private function set_flush_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0]];
        if (array_key_exists(1, $arguments) && null !== $arguments[1]) {
            $processed_arguments[] = strtoupper($arguments[1]);
        }
        parent::set_arguments($processed_arguments);
    }
    private function set_list_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0]];
        if (array_key_exists(1, $arguments) && null !== $arguments[1]) {
            array_push($processed_arguments, 'LIBRARYNAME', $arguments[1]);
        }
        if (array_key_exists(2, $arguments) && true === $arguments[2]) {
            $processed_arguments[] = 'WITHCODE';
        }
        parent::set_arguments($processed_arguments);
    }
    private function set_load_arguments(array $arguments): void
    {
        if (count($arguments) <= 2) {
            parent::set_arguments($arguments);
            return;
        }
        $processed_arguments = [$arguments[0]];
        $replace = array_pop($arguments);
        if (is_bool($replace) && $replace) {
            $processed_arguments[] = 'REPLACE';
        } elseif (!is_bool($replace)) {
            $processed_arguments[] = $replace;
        }
        $processed_arguments[] = $arguments[1];
        parent::set_arguments($processed_arguments);
    }
    private function set_restore_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0], $arguments[1]];
        if (array_key_exists(2, $arguments) && null !== $arguments[2]) {
            $processed_arguments[] = strtoupper($arguments[2]);
        }
        parent::set_arguments($processed_arguments);
    }
    public function parse_response($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        if ($data === array_values($data)) {
            return array_map(function ($item) {
                return $this->parse_response($item);
            }, $data);
        }
        // Relay
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = $key;
            $result[] = $this->parse_response($value);
        }
        return $result;
    }
}