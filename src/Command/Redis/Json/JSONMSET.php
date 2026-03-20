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
namespace Predis\Command\Redis\Json;

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see https://redis.io/commands/json.mset/
 *
 * Set or update one or more JSON values according to the specified key-path-value triplets.
 */
class JSONMSET extends Redis_Command
{
    public function get_id(): string
    {
        return 'JSON.MSET';
    }
    public function prefix_keys($prefix): void
    {
        if ($arguments = $this->get_arguments()) {
            for ($i = 0, $l = count($arguments); $i < $l; $i += 3) {
                $arguments[$i] = $prefix . $arguments[$i];
            }
            $this->set_arguments($arguments);
        }
    }
}