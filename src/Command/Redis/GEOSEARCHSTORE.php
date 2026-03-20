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
use Predis\Command\Traits\By\Geo_By;
use Predis\Command\Traits\Count;
use Predis\Command\Traits\From\Geo_From;
use Predis\Command\Traits\Sorting;
use Predis\Command\Traits\Storedist;
/**
 * @see https://redis.io/commands/geosearchstore/
 *
 * This command is like GEOSEARCH, but stores the result in destination key.
 */
class GEOSEARCHSTORE extends Redis_Command
{
    use Geo_From {
        Geo_From::setArguments as setFrom;
    }
    use Geo_By {
        Geo_By::setArguments as setBy;
    }
    use Sorting {
        Sorting::setArguments as setSorting;
    }
    use Count {
        Count::setArguments as setCount;
    }
    use Storedist {
        Storedist::setArguments as setStoreDist;
    }
    protected static $sort_argument_position_offset = 4;
    protected static $count_argument_position_offset = 5;
    protected static $store_dist_argument_position_offset = 7;
    public function get_id(): string
    {
        return 'GEOSEARCHSTORE';
    }
    public function set_arguments(array $arguments): void
    {
        $this->set_store_dist($arguments);
        $arguments = $this->get_arguments();
        $this->set_count($arguments, $arguments[6] ?? false);
        $arguments = $this->get_arguments();
        $this->set_sorting($arguments);
        $arguments = $this->get_arguments();
        $this->set_from($arguments);
        $arguments = $this->get_arguments();
        $this->set_by($arguments);
        $this->filter_arguments();
    }
}