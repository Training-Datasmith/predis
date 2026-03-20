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
 * @see https://redis.io/commands/tdigest.min/
 *
 * Returns the minimum observation value from a t-digest sketch.
 */
class TDIGESTMIN extends Redis_Command
{
    public function get_id(): string
    {
        return 'TDIGEST.MIN';
    }
    /**
     * {@inheritdoc}
     */
    public function parse_response($data)
    {
        if (is_string($data) || !is_float($data)) {
            return $data;
        }
        // convert Relay (RESP3) constants to strings
        if (is_nan($data)) {
            return 'nan';
        }
        switch ($data) {
            case INF:
                return 'inf';
            case -INF:
                return '-inf';
            default:
                return $data;
        }
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}