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
use Predis\Command\Traits\Bit_Byte;
/**
 * @see http://redis.io/commands/bitpos
 *
 * Return the position of the first bit set to 1 or 0 in a string.
 */
class BITPOS extends Redis_Command
{
    use Bit_Byte;
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'BITPOS';
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}