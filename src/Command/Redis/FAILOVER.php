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
use Predis\Command\Traits\Timeout;
use Predis\Command\Traits\To\Server_To;
class FAILOVER extends Redis_Command
{
    use Server_To {
        Server_To::setArguments as setTo;
    }
    use Timeout {
        Timeout::setArguments as setTimeout;
    }
    protected static $to_argument_position_offset = 0;
    protected static $timeout_argument_position_offset = 2;
    public function get_id(): string
    {
        return 'FAILOVER';
    }
    public function set_arguments(array $arguments): void
    {
        if (array_key_exists(1, $arguments) && false !== $arguments[1]) {
            $arguments[1] = 'ABORT';
        }
        $this->set_timeout($arguments);
        $arguments = $this->get_arguments();
        $this->set_to($arguments);
        $this->filter_arguments();
    }
}