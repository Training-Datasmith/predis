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
/**
 * @see http://redis.io/commands/client-list
 * @see http://redis.io/commands/client-kill
 * @see http://redis.io/commands/client-getname
 * @see http://redis.io/commands/client-setname
 */
class CLIENT extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'CLIENT';
    }
    public function set_arguments(array $arguments): void
    {
        switch ($arguments[0]) {
            case 'LIST':
                $this->set_list_arguments($arguments);
                break;
            case 'NOEVICT':
                $arguments[0] = 'NO-EVICT';
                $this->set_no_touch_arguments($arguments);
                break;
            case 'NOTOUCH':
                $arguments[0] = 'NO-TOUCH';
                $this->set_no_touch_arguments($arguments);
                break;
            case 'SETINFO':
                $this->set_set_info_arguments($arguments);
                break;
            default:
                parent::set_arguments($arguments);
        }
    }
    private function set_list_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0]];
        if (array_key_exists(1, $arguments) && null !== $arguments[1]) {
            array_push($processed_arguments, 'TYPE', strtoupper($arguments[1]));
        }
        if (array_key_exists(2, $arguments)) {
            array_push($processed_arguments, 'ID', $arguments[2]);
        }
        if (count($arguments) > 3) {
            for ($i = 3, $i_max = count($arguments); $i < $i_max; $i++) {
                $processed_arguments[] = $arguments[$i];
            }
        }
        parent::set_arguments($processed_arguments);
    }
    private function set_no_touch_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0]];
        if (array_key_exists(1, $arguments) && null !== $arguments[1]) {
            $modifier = $arguments[1] ? 'ON' : 'OFF';
            $processed_arguments[] = $modifier;
        }
        parent::set_arguments($processed_arguments);
    }
    private function set_set_info_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0]];
        if (array_key_exists(1, $arguments) && null !== $arguments[1] && array_key_exists(2, $arguments) && null !== $arguments[2]) {
            array_push($processed_arguments, strtoupper($arguments[1]), $arguments[2]);
        }
        parent::set_arguments($processed_arguments);
    }
    /**
     * {@inheritdoc}
     */
    public function parse_response($data)
    {
        $args = array_change_key_case($this->get_arguments(), CASE_UPPER);
        switch (strtoupper($args[0])) {
            case 'LIST':
                return $this->parse_client_list($data);
            case 'KILL':
            case 'GETNAME':
            case 'SETNAME':
            default:
                return $data;
        }
        // @codeCoverageIgnore
    }
    /**
     * Parses the response to CLIENT LIST and returns a structured list.
     *
     * @param string $data Response buffer.
     */
    protected function parse_client_list($data): array
    {
        $clients = [];
        foreach (explode("\n", $data, -1) as $client_data) {
            $client = [];
            foreach (explode(' ', $client_data) as $kv) {
                @[$k, $v] = explode('=', $kv);
                $client[$k] = $v;
            }
            $clients[] = $client;
        }
        return $clients;
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