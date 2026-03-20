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
 * @see http://redis.io/commands/shutdown
 */
class SHUTDOWN extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'SHUTDOWN';
    }
    /**
     * {@inheritdoc}
     */
    public function set_arguments(array $arguments): void
    {
        if (empty($arguments)) {
            parent::set_arguments($arguments);
            return;
        }
        $processed_arguments = [];
        if (array_key_exists(0, $arguments) && null !== $arguments[0]) {
            $processed_arguments[] = $arguments[0] ? 'SAVE' : 'NOSAVE';
        }
        if (array_key_exists(1, $arguments) && false !== $arguments[1]) {
            $processed_arguments[] = 'NOW';
        }
        if (array_key_exists(2, $arguments) && false !== $arguments[2]) {
            $processed_arguments[] = 'FORCE';
        }
        if (array_key_exists(3, $arguments) && false !== $arguments[3]) {
            $processed_arguments[] = 'ABORT';
        }
        parent::set_arguments($processed_arguments);
    }
}