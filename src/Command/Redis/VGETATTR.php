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
class VGETATTR extends Redis_Command
{
    /**
     * @var bool
     */
    private $as_json = false;
    public function get_id(): string
    {
        return 'VGETATTR';
    }
    public function set_arguments(array $arguments): void
    {
        $last_arg = array_pop($arguments);
        if (is_bool($last_arg)) {
            $this->as_json = $last_arg;
        } else {
            $arguments[] = $last_arg;
        }
        parent::set_arguments($arguments);
    }
    /**
     * @param                          $data
     * @return array|mixed|string|null
     */
    public function parse_response($data)
    {
        if (!$this->as_json && !is_null($data)) {
            return json_decode($data, true);
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
}