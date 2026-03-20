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
 * @see https://redis.io/commands/tdigest.cdf/
 *
 * Returns, for each input value, an estimation of the fraction (floating-point)
 * of (observations smaller than the given value + half
 * the observations equal to the given value).
 */
class TDIGESTCDF extends Redis_Command
{
    public function get_id(): string
    {
        return 'TDIGEST.CDF';
    }
    /**
     * {@inheritdoc}
     */
    public function parse_response($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        // convert Relay (RESP3) constants to strings
        return array_map(static function ($value) {
            if (is_string($value) || !is_float($value)) {
                return $value;
            }
            if (is_nan($value)) {
                return 'nan';
            }
            switch ($value) {
                case INF:
                    return 'inf';
                case -INF:
                    return '-inf';
                default:
                    return $value;
            }
        }, $data);
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}