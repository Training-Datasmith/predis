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
use Predis\Command\Traits\Count;
use Predis\Command\Traits\Keys;
use Predis\Command\Traits\Min_Max_Modifier;
/**
 * @see https://redis.io/commands/zmpop/
 *
 * Pops one or more elements, that are member-score pairs,
 * from the first non-empty sorted set in the provided list of key names.
 */
class ZMPOP extends Redis_Command
{
    use Keys {
        Keys::setArguments as setKeys;
    }
    use Count {
        Count::setArguments as setCount;
    }
    use Min_Max_Modifier;
    protected static $keys_argument_position_offset = 0;
    protected static $count_argument_position_offset = 2;
    protected static $modifier_argument_position_offset = 1;
    public function get_id(): string
    {
        return 'ZMPOP';
    }
    public function set_arguments(array $arguments): void
    {
        $this->set_count($arguments);
        $arguments = $this->get_arguments();
        $this->resolve_modifier(static::$modifier_argument_position_offset, $arguments);
        $this->set_keys($arguments);
        $arguments = $this->get_arguments();
        parent::set_arguments($arguments);
    }
    /**
     * @return mixed[]
     */
    public function parse_response($data): array
    {
        $key = array_shift($data);
        if (null === $key) {
            return [$key];
        }
        $data = $data[0];
        $parsed_data = [];
        for ($i = 0, $i_max = count($data); $i < $i_max; $i++) {
            for ($j = 0, $j_max = count($data[$i]); $j < $j_max; ++$j) {
                if ($data[$i][$j + 1] ?? false) {
                    $parsed_data[$data[$i][$j]] = $data[$i][++$j];
                }
            }
        }
        return array_combine([$key], [$parsed_data]);
    }
    /**
     * @param                                               $data
     * @return array|array[]|false|mixed|null[]|string|null
     */
    public function parse_resp3response($data)
    {
        return $this->parse_response($data);
    }
}