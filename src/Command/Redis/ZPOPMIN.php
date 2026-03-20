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
 * @see http://redis.io/commands/zpopmin
 */
class ZPOPMIN extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'ZPOPMIN';
    }
    /**
     * {@inheritdoc}
     * @return mixed[]
     */
    public function parse_response($data): array
    {
        $result = [];
        for ($i = 0; $i < count($data); ++$i) {
            if (is_array($data[$i])) {
                $result[$data[$i][0]] = $data[$i][1];
                // Relay
            } else {
                $result[$data[$i]] = $data[++$i];
            }
        }
        return $result;
    }
    /**
     * @param                          $data
     * @return array|mixed|string|null
     */
    public function parse_resp3response($data)
    {
        $parsed_data = [];
        foreach ($data as $element) {
            if (is_array($element)) {
                $parsed_data[] = $this->parse_response($element);
            } else {
                return $this->parse_response($data);
            }
        }
        return $parsed_data;
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}