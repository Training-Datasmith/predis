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
class VLINKS extends Redis_Command
{
    /**
     * @var bool
     */
    private $with_scores = false;
    public function get_id(): string
    {
        return 'VLINKS';
    }
    public function set_arguments(array $arguments): void
    {
        $last_arg = array_pop($arguments);
        if (is_bool($last_arg)) {
            $this->with_scores = $last_arg;
            $arguments[] = 'WITHSCORES';
        } else {
            $arguments[] = $last_arg;
        }
        parent::set_arguments($arguments);
    }
    /**
     * @param             $data
     */
    public function parse_response($data): ?array
    {
        if (!is_null($data)) {
            if ($this->with_scores) {
                foreach ($data as $key => $value) {
                    if ($value === array_values($value)) {
                        $data[$key] = Command_Utility::array_to_dictionary($value, static function ($key, $value): array {
                            return [$key, (float) $value];
                        });
                    } else {
                        $data[$key] = $value;
                    }
                }
            }
        }
        return $data;
    }
}