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
namespace Predis\Command\Redis\T_Digest;

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see https://redis.io/commands/tdigest.create/
 *
 * Allocates memory and initializes a new t-digest sketch.
 */
class TDIGESTCREATE extends Redis_Command
{
    public function get_id(): string
    {
        return 'TDIGEST.CREATE';
    }
    public function set_arguments(array $arguments): void
    {
        if (!empty($arguments[1])) {
            $arguments[2] = $arguments[1];
            $arguments[1] = 'COMPRESSION';
        } elseif (array_key_exists(1, $arguments) && $arguments[1] < 1) {
            array_pop($arguments);
        }
        parent::set_arguments($arguments);
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}