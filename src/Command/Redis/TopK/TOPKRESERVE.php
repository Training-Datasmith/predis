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
namespace Predis\Command\Redis\Top_K;

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see https://redis.io/commands/topk.reserve/
 *
 * Initializes a TopK with specified parameters.
 */
class TOPKRESERVE extends Redis_Command
{
    public function get_id(): string
    {
        return 'TOPK.RESERVE';
    }
    public function set_arguments(array $arguments): void
    {
        switch (count($arguments)) {
            case 3:
                $arguments[] = 7;
                // default depth
                $arguments[] = 0.9;
                // default decay
                break;
            case 4:
                $arguments[] = 0.9;
                // default decay
                break;
            default:
                parent::set_arguments($arguments);
                return;
        }
        parent::set_arguments($arguments);
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}