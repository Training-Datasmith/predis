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
namespace Predis\Command\Redis\Top_K;

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see https://redis.io/commands/topk.info/
 *
 * Returns number of required items (k), width, depth and decay values.
 */
class TOPKINFO extends Redis_Command
{
    public function get_id(): string
    {
        return 'TOPK.INFO';
    }
    /**
     * @return mixed[]
     */
    public function parse_response($data): array
    {
        $result = [];
        for ($i = 0, $i_max = count($data); $i < $i_max; ++$i) {
            if (array_key_exists($i + 1, $data)) {
                $result[(string) $data[$i]] = $data[++$i];
            }
        }
        return $result;
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}