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

use Predis\Command\Command as RedisCommand;
/**
 * @see http://redis.io/commands/slowlog
 */
class SLOWLOG extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'SLOWLOG';
    }
    /**
     * {@inheritdoc}
     */
    public function parse_response($data)
    {
        if (is_array($data)) {
            $log = [];
            foreach ($data as $index => $entry) {
                $log[$index] = ['id' => $entry[0], 'timestamp' => $entry[1], 'duration' => $entry[2], 'command' => $entry[3]];
            }
            return $log;
        }
        return $data;
    }
    /**
     * @param                          $data
     * @return array|mixed|string|null
     */
    public function parse_resp3response($data)
    {
        return $this->parse_response($data);
    }
}