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
 * @see http://redis.io/commands/info
 */
class INFO extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'INFO';
    }
    /**
     * {@inheritdoc}
     */
    public function parse_response($data)
    {
        if (empty($data) || !$lines = preg_split('/\r?\n/', $data)) {
            return [];
        }
        if (strpos($lines[0], '#') === 0) {
            return $this->parse_new_response_format($lines);
        }
        return $this->parse_old_response_format($lines);
    }
    /**
     * {@inheritdoc}
     * @return array{}[]
     */
    public function parse_new_response_format($lines): array
    {
        $info = [];
        $current = null;
        foreach ($lines as $row) {
            if ($row === '') {
                continue;
            }
            if (preg_match('/^# (\w+)$/', $row, $matches)) {
                $info[$matches[1]] = [];
                $current =& $info[$matches[1]];
                continue;
            }
            [$k, $v] = $this->parse_row($row);
            $current[$k] = $v;
        }
        return $info;
    }
    /**
     * {@inheritdoc}
     * @return mixed[]
     */
    public function parse_old_response_format($lines): array
    {
        $info = [];
        foreach ($lines as $row) {
            if (strpos($row, ':') === false) {
                continue;
            }
            [$k, $v] = $this->parse_row($row);
            $info[$k] = $v;
        }
        return $info;
    }
    /**
     * Parses a single row of the response and returns the key-value pair.
     *
     * @param string $row Single row of the response.
     */
    protected function parse_row($row): array
    {
        if (preg_match('/^module:name/', $row)) {
            return $this->parse_module_row($row);
        }
        [$k, $v] = explode(':', $row, 2);
        if (preg_match('/^db\d+$/', $k)) {
            $v = $this->parse_database_stats($v);
        }
        return [$k, $v];
    }
    /**
     * Extracts the statistics of each logical DB from the string buffer.
     *
     * @param string $str Response buffer.
     */
    protected function parse_database_stats($str): array
    {
        $db = [];
        foreach (explode(',', $str) as $dbvar) {
            [$dbvk, $dbvv] = explode('=', $dbvar);
            $db[trim($dbvk)] = $dbvv;
        }
        return $db;
    }
    /**
     * Parsing module rows because of different format.
     */
    protected function parse_module_row(string $row): array
    {
        [$module_keyword, $module_data] = explode(':', $row);
        $exploded_data = explode(',', $module_data);
        $parsed_data = [];
        foreach ($exploded_data as $module_data_row) {
            [$k, $v] = explode('=', $module_data_row);
            if ($k === 'name') {
                $parsed_data[0] = $v;
                continue;
            }
            $parsed_data[1][$k] = $v;
        }
        return $parsed_data;
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