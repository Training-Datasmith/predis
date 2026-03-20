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
namespace Predis\Command\Redis\Bloom_Filter;

use Predis\Command\Prefixable_Command as RedisCommand;
use Predis\Command\Traits\Bloom_Filters\Capacity;
use Predis\Command\Traits\Bloom_Filters\Error;
use Predis\Command\Traits\Bloom_Filters\Expansion;
use Predis\Command\Traits\Bloom_Filters\Items;
use Predis\Command\Traits\Bloom_Filters\No_Create;
class BFINSERT extends Redis_Command
{
    use Capacity {
        Capacity::setArguments as setCapacity;
    }
    use Error {
        Error::setArguments as setErrorRate;
    }
    use Expansion {
        Expansion::setArguments as setExpansion;
    }
    use Items {
        Items::setArguments as setItems;
    }
    use No_Create {
        No_Create::setArguments as setNoCreate;
    }
    protected static $capacity_argument_position_offset = 1;
    protected static $error_argument_position_offset = 2;
    protected static $expansion_argument_position_offset = 3;
    protected static $no_create_argument_position_offset = 4;
    protected static $items_argument_position_offset = 6;
    public function get_id(): string
    {
        return 'BF.INSERT';
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
    public function set_arguments(array $arguments): void
    {
        $this->set_no_create($arguments);
        $arguments = $this->get_arguments();
        if (array_key_exists(5, $arguments) && $arguments[5]) {
            $arguments[5] = 'NONSCALING';
        }
        $this->set_items($arguments);
        $arguments = $this->get_arguments();
        $this->set_expansion($arguments);
        $arguments = $this->get_arguments();
        $this->set_error_rate($arguments);
        $arguments = $this->get_arguments();
        $this->set_capacity($arguments);
        $this->filter_arguments();
    }
}