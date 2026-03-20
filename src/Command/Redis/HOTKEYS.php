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
use Predis\Command\Redis\Utils\Command_Utility;
use Value_Error;
class HOTKEYS extends Redis_Command
{
    /**
     * {@inheritDoc}
     */
    public function get_id(): string
    {
        return 'HOTKEYS';
    }
    public function set_arguments(array $arguments): void
    {
        switch ($arguments[0]) {
            case 'START':
                $this->set_start_arguments($arguments);
                break;
            default:
                parent::set_arguments($arguments);
        }
    }
    public function parse_response($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $item) {
                $dict = Command_Utility::array_to_dictionary($item, null, false);
                $data[$key] = $dict;
            }
        }
        return $data;
    }
    private function set_start_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0]];
        array_push($processed_arguments, 'METRICS', count($arguments[1]), ...$arguments[1]);
        if (isset($arguments[2])) {
            if ($arguments[2] > 9 && $arguments[2] < 65) {
                array_push($processed_arguments, 'COUNT', $arguments[2]);
            } else {
                throw new Value_Error('Count value should be between 10 and 64');
            }
        }
        if (isset($arguments[3])) {
            array_push($processed_arguments, 'DURATION', $arguments[3]);
        }
        if (isset($arguments[4])) {
            if ($arguments[4] > 0) {
                array_push($processed_arguments, 'SAMPLE', $arguments[4]);
            } else {
                throw new Value_Error('Sample value should be greater than 0');
            }
        }
        if (isset($arguments[5])) {
            array_push($processed_arguments, 'SLOTS', count($arguments[5]), ...$arguments[5]);
        }
        parent::set_arguments($processed_arguments);
    }
}