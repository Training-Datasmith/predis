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

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see https://redis.io/commands/?name=xgroup
 *
 * Container command corresponds to any XGROUP *.
 * Represents any XGROUP command with subcommand as first argument.
 */
class XGROUP extends Redis_Command
{
    public function get_id(): string
    {
        return 'XGROUP';
    }
    public function set_arguments(array $arguments): void
    {
        switch ($arguments[0]) {
            case 'CREATE':
                $this->set_create_arguments($arguments);
                return;
            case 'SETID':
                $this->set_set_id_arguments($arguments);
                return;
            default:
                parent::set_arguments($arguments);
        }
    }
    private function set_create_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0], $arguments[1], $arguments[2], $arguments[3]];
        if (array_key_exists(4, $arguments) && true === $arguments[4]) {
            $processed_arguments[] = 'MKSTREAM';
        }
        if (array_key_exists(5, $arguments)) {
            array_push($processed_arguments, 'ENTRIESREAD', $arguments[5]);
        }
        parent::set_arguments($processed_arguments);
    }
    private function set_set_id_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0], $arguments[1], $arguments[2], $arguments[3]];
        if (array_key_exists(4, $arguments)) {
            array_push($processed_arguments, 'ENTRIESREAD', $arguments[4]);
        }
        parent::set_arguments($processed_arguments);
    }
    public function prefix_keys($prefix): void
    {
        $arguments = $this->get_arguments();
        $arguments[1] = $prefix . $arguments[1];
        $this->set_raw_arguments($arguments);
    }
}