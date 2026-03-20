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
 * @see http://redis.io/commands/zscan
 */
class ZSCAN extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'ZSCAN';
    }
    /**
     * {@inheritdoc}
     */
    public function set_arguments(array $arguments): void
    {
        if (count($arguments) === 3 && is_array($arguments[2])) {
            $options = $this->prepare_options(array_pop($arguments));
            $arguments = array_merge($arguments, $options);
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
        $options = array_change_key_case($options, CASE_UPPER);
        $normalized = [];
        if (!empty($options['MATCH'])) {
            $normalized[] = 'MATCH';
            $normalized[] = $options['MATCH'];
        }
        if (!empty($options['COUNT'])) {
            $normalized[] = 'COUNT';
            $normalized[] = $options['COUNT'];
        }
        return $normalized;
    }
    /**
     * {@inheritdoc}
     */
    public function parse_response($data)
    {
        if (is_array($data)) {
            $members = $data[1];
            $result = [];
            for ($i = 0; $i < count($members); ++$i) {
                $result[$members[$i]] = (float) $members[++$i];
            }
            $data[1] = $result;
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
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}