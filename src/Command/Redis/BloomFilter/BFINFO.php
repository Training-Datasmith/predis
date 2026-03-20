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
namespace Predis\Command\Redis\Bloom_Filter;

use Predis\Command\Prefixable_Command as RedisCommand;
use UnexpectedValueException;
/**
 * @see https://redis.io/commands/bf.info/
 *
 * Return information about key filter.
 */
class BFINFO extends Redis_Command
{
    /**
     * @var string[]
     */
    private $modifier_enum = ['capacity' => 'CAPACITY', 'size' => 'SIZE', 'filters' => 'FILTERS', 'items' => 'ITEMS', 'expansion' => 'EXPANSION'];
    public function get_id(): string
    {
        return 'BF.INFO';
    }
    public function set_arguments(array $arguments): void
    {
        if (isset($arguments[1])) {
            $modifier = array_pop($arguments);
            if ($modifier === '') {
                parent::set_arguments($arguments);
                return;
            }
            if (!in_array(strtoupper($modifier), $this->modifier_enum)) {
                $enum_values = implode(', ', array_keys($this->modifier_enum));
                throw new UnexpectedValueException("Argument accepts only: {$enum_values} values");
            }
            $arguments[] = $this->modifier_enum[strtolower($modifier)];
        }
        parent::set_arguments($arguments);
    }
    public function parse_response($data)
    {
        if (count($data) > 1) {
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
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}