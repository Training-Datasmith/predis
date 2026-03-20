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
namespace Predis\Command\Redis\Cuckoo_Filter;

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see https://redis.io/commands/cf.addnx/
 *
 * Adds an item to a cuckoo filter if the item did not exist previously.
 */
class CFADDNX extends Redis_Command
{
    public function get_id(): string
    {
        return 'CF.ADDNX';
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}