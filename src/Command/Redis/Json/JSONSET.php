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
namespace Predis\Command\Redis\Json;

use Predis\Command\Prefixable_Command as RedisCommand;
use Predis\Command\Traits\Json\Nx_Xx_Argument;
/**
 * @see https://redis.io/commands/json.set/
 *
 * Set the JSON value at path in key
 */
class JSONSET extends Redis_Command
{
    use Nx_Xx_Argument {
        setArguments as setSubcommand;
    }
    protected static $nx_xx_argument_position_offset = 3;
    public function get_id(): string
    {
        return 'JSON.SET';
    }
    public function set_arguments(array $arguments): void
    {
        $this->set_subcommand($arguments);
        $this->filter_arguments();
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}