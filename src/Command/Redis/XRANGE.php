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
 * @see http://redis.io/commands/xrange
 */
class XRANGE extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'XRANGE';
    }
    /**
     * {@inheritdoc}
     */
    public function set_arguments(array $arguments): void
    {
        if (count($arguments) === 4) {
            $arguments[] = $arguments[3];
            $arguments[3] = 'COUNT';
        }
        parent::set_arguments($arguments);
    }
    /**
     * {@inheritdoc}
     * @return mixed[][]
     */
    public function parse_response($data): array
    {
        $result = [];
        foreach ($data as $entry) {
            $processed = [];
            $count = count($entry[1]);
            for ($i = 0; $i < $count; ++$i) {
                $processed[$entry[1][$i]] = $entry[1][++$i];
            }
            $result[$entry[0]] = $processed;
        }
        return $result;
    }
    public function parse_resp3response($data)
    {
        return $this->parse_response($data);
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}