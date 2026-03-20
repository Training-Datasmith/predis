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
class VSIM extends Redis_Command
{
    private $with_scores = false;
    public function get_id(): string
    {
        return 'VSIM';
    }
    public function set_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0]];
        if (isset($arguments[1]) && !is_array($arguments[1])) {
            if (isset($arguments[2]) && false !== $arguments[2]) {
                array_push($processed_arguments, 'ELE', $arguments[1]);
            } else {
                array_push($processed_arguments, 'FP32', $arguments[1]);
            }
        } else {
            array_push($processed_arguments, 'VALUES', count($arguments[1]), ...$arguments[1]);
        }
        if (isset($arguments[3]) && false !== $arguments[3]) {
            $this->with_scores = true;
            $processed_arguments[] = 'WITHSCORES';
        }
        if (isset($arguments[4])) {
            array_push($processed_arguments, 'COUNT', $arguments[4]);
        }
        if (isset($arguments[5])) {
            array_push($processed_arguments, 'EPSILON', $arguments[5]);
        }
        if (isset($arguments[6])) {
            array_push($processed_arguments, 'EF', $arguments[6]);
        }
        if (isset($arguments[7])) {
            array_push($processed_arguments, 'FILTER', $arguments[7]);
        }
        if (isset($arguments[8])) {
            array_push($processed_arguments, 'FILTER-EF', $arguments[8]);
        }
        if (isset($arguments[9]) && false !== $arguments[9]) {
            $processed_arguments[] = 'TRUTH';
        }
        if (isset($arguments[10]) && false !== $arguments[10]) {
            $processed_arguments[] = 'NOTHREAD';
        }
        parent::set_arguments($processed_arguments);
    }
    /**
     * @param                          $data
     * @return array|mixed|string|null
     */
    public function parse_response($data)
    {
        if ($this->with_scores) {
            if ($data === array_values($data)) {
                $data = Command_Utility::array_to_dictionary($data, static function ($key, $value): array {
                    return [$key, (float) $value];
                });
            }
        }
        return $data;
    }
}