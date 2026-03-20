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
class VEMB extends Redis_Command
{
    /**
     * @var bool
     */
    private $is_raw = false;
    public function get_id(): string
    {
        return 'VEMB';
    }
    public function set_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0], $arguments[1]];
        if (isset($arguments[2])) {
            $this->is_raw = true;
            $processed_arguments[] = 'RAW';
        }
        parent::set_arguments($processed_arguments);
    }
    /**
     * @param                            $data
     * @return array|float[]|string|null
     */
    public function parse_response($data): array
    {
        if (!$this->is_raw) {
            return array_map(static function ($value): float {
                return (float) $value;
            }, $data);
        }
        $parsed_data = [];
        for ($i = 0; $i < count($data); $i++) {
            if ($i > 1) {
                $parsed_data[] = (float) $data[$i];
            } else {
                $parsed_data[] = $data[$i];
            }
        }
        return $parsed_data;
    }
}