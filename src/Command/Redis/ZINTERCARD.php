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
use Predis\Command\Traits\Keys;
use Predis\Command\Traits\Limit\Limit;
/**
 * @see https://redis.io/commands/zintercard/
 *
 * This command is similar to ZINTER, but instead of returning the result set,
 * it returns just the cardinality of the result.
 */
class ZINTERCARD extends Redis_Command
{
    use Keys {
        Keys::setArguments as setKeys;
    }
    use Limit {
        Limit::setArguments as setLimit;
    }
    protected static $keys_argument_position_offset = 0;
    protected static $limit_argument_position_offset = 1;
    public function get_id(): string
    {
        return 'ZINTERCARD';
    }
    public function set_arguments(array $arguments): void
    {
        $this->set_limit($arguments);
        $arguments = $this->get_arguments();
        $this->set_keys($arguments);
    }
}