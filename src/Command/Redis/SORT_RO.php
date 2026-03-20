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
use Predis\Command\Traits\By\By_Argument;
use Predis\Command\Traits\Get\Get;
use Predis\Command\Traits\Limit\Limit_Object;
use Predis\Command\Traits\Sorting;
/**
 * @see https://redis.io/commands/sort_ro/
 *
 * Read-only variant of the SORT command.
 * It is exactly like the original SORT but refuses the STORE option
 * and can safely be used in read-only replicas.
 */
class SORT_RO extends Redis_Command
{
    use By_Argument {
        By_Argument::setArguments as setBy;
    }
    use Limit_Object {
        Limit_Object::setArguments as setLimit;
    }
    use Get {
        Get::setArguments as setGetArgument;
    }
    use Sorting {
        Sorting::setArguments as setSorting;
    }
    protected static $by_argument_position_offset = 1;
    protected static $get_argument_position_offset = 3;
    protected static $sort_argument_position_offset = 4;
    public function get_id(): string
    {
        return 'SORT_RO';
    }
    public function set_arguments(array $arguments): void
    {
        $alpha = array_pop($arguments);
        if (is_bool($alpha) && $alpha) {
            $arguments[] = 'ALPHA';
        } elseif (!is_bool($alpha)) {
            $arguments[] = $alpha;
        }
        $this->set_sorting($arguments);
        $arguments = $this->get_arguments();
        $this->set_get_argument($arguments);
        $arguments = $this->get_arguments();
        $this->set_limit($arguments);
        $arguments = $this->get_arguments();
        $this->set_by($arguments);
        $this->filter_arguments();
    }
}