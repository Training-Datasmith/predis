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
 * @see http://redis.io/commands/xpending
 */
class XPENDING extends Redis_Command
{
    public function get_id(): string
    {
        return 'XPENDING';
    }
    public function set_arguments(array $arguments): void
    {
        if (count($arguments) < 2) {
            return;
        }
        $processed_arguments = array_slice($arguments, 0, 2);
        $min_idle_time = $arguments[2] ?? null;
        $start = $arguments[3] ?? null;
        $end = $arguments[4] ?? null;
        $count = $arguments[5] ?? null;
        $consumer = $arguments[6] ?? null;
        if ($start !== null && $end !== null && $count !== null) {
            if ($min_idle_time !== null) {
                array_push($processed_arguments, 'IDLE', $min_idle_time);
            }
            array_push($processed_arguments, $start, $end, $count);
            if ($consumer !== null) {
                $processed_arguments[] = $consumer;
            }
        }
        parent::set_arguments($processed_arguments);
    }
    public function parse_response($data): array
    {
        if ($this->get_argument(2) !== null) {
            return $data;
        }
        [$pending, $min_id, $max_id, $consumers] = $data;
        if (is_array($consumers)) {
            $parsed_consumers = [];
            foreach ($consumers as [$consumer, $num]) {
                $parsed_consumers[$consumer] = (int) $num;
            }
        } else {
            $parsed_consumers = $consumers;
        }
        return [$pending, $min_id, $max_id, $parsed_consumers];
    }
    public function parse_resp3response($data): array
    {
        return $this->parse_response($data);
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}