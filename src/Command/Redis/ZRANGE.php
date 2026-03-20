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

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see http://redis.io/commands/zrange
 */
class ZRANGE extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'ZRANGE';
    }
    /**
     * {@inheritdoc}
     */
    public function set_arguments(array $arguments): void
    {
        if (count($arguments) === 4) {
            $last_type = gettype($arguments[3]);
            if ($last_type === 'string' && strtoupper($arguments[3]) === 'WITHSCORES') {
                // Used for compatibility with older versions
                $arguments[3] = ['WITHSCORES' => true];
                $last_type = 'array';
            }
            if ($last_type === 'array') {
                $options = $this->prepare_options(array_pop($arguments));
                $arguments = array_merge($arguments, $options);
            }
        }
        parent::set_arguments($arguments);
    }
    /**
     * Returns a list of options and modifiers compatible with Redis.
     *
     * @param array $options List of options.
     */
    protected function prepare_options($options): array
    {
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalized_opts = [];
        if (!empty($opts['WITHSCORES'])) {
            $finalized_opts[] = 'WITHSCORES';
        }
        return $finalized_opts;
    }
    /**
     * Checks for the presence of the WITHSCORES modifier.
     *
     * @return bool
     */
    protected function with_scores()
    {
        $arguments = $this->get_arguments();
        if (count($arguments) < 4) {
            return false;
        }
        return strtoupper($arguments[3]) === 'WITHSCORES';
    }
    /**
     * {@inheritdoc}
     */
    public function parse_response($data)
    {
        if ($this->with_scores()) {
            $result = [];
            for ($i = 0; $i < count($data); ++$i) {
                if (is_array($data[$i])) {
                    $result[$data[$i][0]] = $data[$i][1];
                    // Relay
                } else {
                    $result[$data[$i]] = $data[++$i];
                }
            }
            return $result;
        }
        return $data;
    }
    /**
     * @param                          $data
     * @return array|mixed|string|null
     */
    public function parse_resp3response($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        $parsed_data = [];
        foreach ($data as $element) {
            $parsed_data[] = $this->parse_response($element);
        }
        return $parsed_data;
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}