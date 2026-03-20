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
 * @see http://redis.io/commands/xadd
 */
class XADD extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'XADD';
    }
    /**
     * {@inheritdoc}
     */
    public function set_arguments(array $arguments): void
    {
        $args = [];
        $args[] = $arguments[0];
        $options = $arguments[3] ?? [];
        if (isset($options['nomkstream']) && $options['nomkstream']) {
            $args[] = 'NOMKSTREAM';
        }
        if (isset($options['trimming'])) {
            $args[] = strtoupper($options['trimming']);
        }
        // IDMPAUTO or IDMP options (mutually exclusive)
        if (isset($options['idmpauto'])) {
            $args[] = 'IDMPAUTO';
            $args[] = $options['idmpauto'];
        } elseif (isset($options['idmp']) && is_array($options['idmp'])) {
            $args[] = 'IDMP';
            $args[] = $options['idmp'][0];
            // pid
            $args[] = $options['idmp'][1];
            // iid
        }
        if (isset($options['trim']) && is_array($options['trim'])) {
            array_push($args, ...$options['trim']);
            if (isset($options['limit'])) {
                $args[] = 'LIMIT';
                $args[] = $options['limit'];
            }
        }
        // ID, default to * to let Redis set it
        $args[] = $arguments[2] ?? '*';
        if (isset($arguments[1]) && is_array($arguments[1])) {
            foreach ($arguments[1] as $key => $val) {
                $args[] = $key;
                $args[] = $val;
            }
        }
        parent::set_arguments($args);
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}