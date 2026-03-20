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

use Predis\Command\Prefixable_Command;
use Value_Error;
class MSETEX extends Prefixable_Command
{
    /**
     * {@inheritDoc}
     */
    public function get_id(): string
    {
        return 'MSETEX';
    }
    public function set_arguments(array $arguments): void
    {
        $processed_arguments = [count(array_keys($arguments[0]))];
        array_walk($arguments[0], static function ($value, $key) use (&$processed_arguments): void {
            array_push($processed_arguments, $key, $value);
        });
        if (isset($arguments[1])) {
            if (!in_array(strtoupper($arguments[1]), ['NX', 'XX'])) {
                throw new Value_Error('Incorrect exist modifier. Should be one of: NX, XX.');
            }
            $processed_arguments[] = strtoupper($arguments[1]);
        }
        if (isset($arguments[2])) {
            if (!isset($arguments[3]) && strtoupper($arguments[2]) !== 'KEEPTTL') {
                throw new Value_Error('TTL should be specified along with expire resolution parameter');
            }
            if (!in_array(strtoupper($arguments[2]), ['EX', 'PX', 'EXAT', 'PXAT', 'KEEPTTL'])) {
                throw new Value_Error('Incorrect expire modifier. Should be one of: EX, PX, EXAT, PXAT, KEEPTTL');
            }
            if (strtoupper($arguments[2]) !== 'KEEPTTL') {
                array_push($processed_arguments, strtoupper($arguments[2]), $arguments[3]);
            } else {
                $processed_arguments[] = strtoupper($arguments[2]);
            }
        }
        parent::set_arguments($processed_arguments);
    }
    /**
     * {@inheritDoc}
     */
    public function prefix_keys($prefix): void
    {
        $arguments = $this->get_arguments();
        $keys_count = $arguments[0];
        $current_key_index = 1;
        while ($keys_count > 0) {
            $arguments[$current_key_index] = $prefix . $arguments[$current_key_index];
            $keys_count--;
            $current_key_index += 2;
        }
        parent::set_raw_arguments($arguments);
    }
}