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
 * @see http://redis.io/commands/hgetall
 */
class HGETALL extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'HGETALL';
    }
    /**
     * {@inheritdoc}
     */
    public function parse_response($data)
    {
        if ($data !== array_values($data)) {
            return $data;
            // Relay
        }
        $result = [];
        for ($i = 0; $i < count($data); ++$i) {
            $result[$data[$i]] = $data[++$i];
        }
        return $result;
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}