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
namespace Predis\Command\Redis\Cuckoo_Filter;

use Predis\Command\Prefixable_Command as RedisCommand;
use Predis\Command\Traits\Bloom_Filters\Capacity;
use Predis\Command\Traits\Bloom_Filters\Items;
use Predis\Command\Traits\Bloom_Filters\No_Create;
class CFINSERT extends Redis_Command
{
    use Capacity {
        Capacity::setArguments as setCapacity;
    }
    use No_Create {
        No_Create::setArguments as setNoCreate;
    }
    use Items {
        Items::setArguments as setItems;
    }
    protected static $capacity_argument_position_offset = 1;
    protected static $no_create_argument_position_offset = 2;
    protected static $items_argument_position_offset = 3;
    public function get_id(): string
    {
        return 'CF.INSERT';
    }
    public function set_arguments(array $arguments): void
    {
        $this->set_no_create($arguments);
        $arguments = $this->get_arguments();
        $this->set_items($arguments);
        $arguments = $this->get_arguments();
        $this->set_capacity($arguments);
        $this->filter_arguments();
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}