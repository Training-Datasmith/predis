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
use Predis\Command\Traits\Expire\Expire_Options;
/**
 * @see http://redis.io/commands/expire
 *
 * Set a timeout on key.
 * After the timeout has expired, the key will automatically be deleted.
 * A key with an associated timeout is often said to be volatile in Redis terminology.
 */
class EXPIRE extends Redis_Command
{
    use Expire_Options;
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'EXPIRE';
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}