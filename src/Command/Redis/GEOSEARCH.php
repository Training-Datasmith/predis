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
use Predis\Command\Traits\By\Geo_By;
use Predis\Command\Traits\Count;
use Predis\Command\Traits\From\Geo_From;
use Predis\Command\Traits\Sorting;
use Predis\Command\Traits\With\With_Coord;
use Predis\Command\Traits\With\With_Dist;
use Predis\Command\Traits\With\With_Hash;
/**
 * @see https://redis.io/commands/geosearch/
 *
 * Return the members of a sorted set populated with geospatial information using GEOADD,
 * which are within the borders of the area specified by a given shape.
 *
 * This command extends the GEORADIUS command, so in addition to searching
 * within circular areas, it supports searching within rectangular areas.
 */
class GEOSEARCH extends Redis_Command
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
    use With_Coord {
        With_Coord::setArguments as setWithCoord;
    }
    use With_Dist {
        With_Dist::setArguments as setWithDist;
    }
    use With_Hash {
        With_Hash::setArguments as setWithHash;
    }
    protected static $sort_argument_position_offset = 3;
    protected static $count_argument_position_offset = 4;
    protected static $with_coord_argument_position_offset = 6;
    protected static $with_dist_argument_position_offset = 7;
    protected static $with_hash_argument_position_offset = 8;
    public function get_id(): string
    {
        return 'GEOSEARCH';
    }
    public function set_arguments(array $arguments): void
    {
        $this->set_sorting($arguments);
        $arguments = $this->get_arguments();
        $this->set_with_coord($arguments);
        $arguments = $this->get_arguments();
        $this->set_with_dist($arguments);
        $arguments = $this->get_arguments();
        $this->set_with_hash($arguments);
        $arguments = $this->get_arguments();
        $this->set_count($arguments, $arguments[5] ?? false);
        $arguments = $this->get_arguments();
        $this->set_from($arguments);
        $arguments = $this->get_arguments();
        $this->set_by($arguments);
        $this->filter_arguments();
    }
    /**
     * @return mixed[]
     */
    public function parse_response($data): array
    {
        $parsed_data = [];
        $item_key = '';
        foreach ($data as $item) {
            if (!is_array($item)) {
                $parsed_data[] = $item;
                continue;
            }
            foreach ($item as $key => $item_row) {
                if ($key === 0) {
                    $item_key = $item_row;
                    continue;
                }
                if (is_string($item_row)) {
                    $parsed_data[$item_key]['dist'] = round((float) $item_row, 5);
                } elseif (is_int($item_row)) {
                    $parsed_data[$item_key]['hash'] = $item_row;
                } else {
                    $parsed_data[$item_key]['lng'] = round($item_row[0], 5);
                    $parsed_data[$item_key]['lat'] = round($item_row[1], 5);
                }
            }
        }
        return $parsed_data;
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}