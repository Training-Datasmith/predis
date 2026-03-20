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

use Predis\Command\Argument\Arrayable_Argument;
use Predis\Command\Command as RedisCommand;
use Predis\Command\Redis\Utils\Command_Utility;
class XINFO extends Redis_Command
{
    public function get_id(): string
    {
        return 'XINFO';
    }
    public function set_arguments(array $arguments): void
    {
        if ($arguments[0] === 'STREAM') {
            $this->set_stream_arguments($arguments);
        } else {
            parent::set_arguments($arguments);
        }
    }
    private function set_stream_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0], $arguments[1]];
        if (array_key_exists(2, $arguments) && $arguments[2] instanceof Arrayable_Argument) {
            $processed_arguments = array_merge($processed_arguments, $arguments[2]->to_array());
        }
        parent::set_arguments($processed_arguments);
    }
    public function parse_response($data): array
    {
        if ($this->get_argument(0) === 'STREAM') {
            return $this->parse_stream_response($data);
        }
        return $this->parse_dict($data);
    }
    private function parse_stream_response($data): array
    {
        if ($data === array_values($data)) {
            $result = Command_Utility::array_to_dictionary($data, null, false);
        } else {
            $result = $data;
            // Relay
        }
        if (isset($result['entries'])) {
            $result['entries'] = $this->parse_dict($result['entries']);
        }
        if (isset($result['groups']) && is_array($result['groups'])) {
            $result['groups'] = array_map(static function (array $group): array {
                if ($group === array_values($group)) {
                    $group = Command_Utility::array_to_dictionary($group, null, false);
                }
                if (isset($group['consumers'])) {
                    $group['consumers'] = array_map(static function ($consumer) {
                        if ($consumer === array_values($consumer)) {
                            return Command_Utility::array_to_dictionary($consumer, null, false);
                        }
                        return $consumer;
                    }, $group['consumers']);
                }
                return $group;
            }, $result['groups']);
        }
        return $result;
    }
    public function parse_resp3response($data)
    {
        $result = $data;
        if (isset($result['entries'])) {
            $result['entries'] = $this->parse_dict($result['entries']);
        }
        return $result;
    }
    private function parse_dict(array $data): array
    {
        if ($data !== array_values($data)) {
            return $data;
            // Relay
        }
        $result = [];
        for ($i = 0, $i_max = count($data); $i < $i_max; $i++) {
            if (is_array($data[$i])) {
                $result[$i] = $this->parse_dict($data[$i]);
                continue;
            }
            if (array_key_exists($i + 1, $data)) {
                if (is_array($data[$i + 1])) {
                    $result[$data[$i]] = $this->parse_dict($data[++$i]);
                } else {
                    $result[$data[$i]] = $data[++$i];
                }
            }
        }
        return $result;
    }
}