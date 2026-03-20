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
 * @see http://redis.io/commands/set
 */
class SET extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'SET';
    }
    public function set_arguments(array $arguments): void
    {
        foreach ($arguments as $index => $value) {
            if ($index < 2) {
                continue;
            }
            if (false === $value || null === $value) {
                unset($arguments[$index]);
            }
        }
        parent::set_arguments($arguments);
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}