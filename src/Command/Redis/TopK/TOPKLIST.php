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
 * @see https://redis.io/commands/topk.list/
 *
 * Return full list of items in Top K list.
 */
class TOPKLIST extends Redis_Command
{
    public function get_id(): string
    {
        return 'TOPK.LIST';
    }
    public function set_arguments(array $arguments): void
    {
        if (!empty($arguments[1])) {
            $arguments[1] = 'WITHCOUNT';
        }
        parent::set_arguments($arguments);
        $this->filter_arguments();
    }
    public function parse_response($data)
    {
        if ($this->is_with_count_modifier()) {
            $result = [];
            for ($i = 0, $i_max = count($data); $i < $i_max; ++$i) {
                if (array_key_exists($i + 1, $data)) {
                    $result[(string) $data[$i]] = $data[++$i];
                }
            }
            return $result;
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
    /**
     * Checks for the presence of the WITHCOUNT modifier.
     */
    private function is_with_count_modifier(): bool
    {
        $arguments = $this->get_arguments();
        $last_argument = !empty($arguments) ? $arguments[count($arguments) - 1] : null;
        return is_string($last_argument) && strtoupper($last_argument) === 'WITHCOUNT';
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}