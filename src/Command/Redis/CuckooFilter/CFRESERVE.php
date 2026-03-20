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
use Predis\Command\Traits\Bloom_Filters\Bucket_Size;
use Predis\Command\Traits\Bloom_Filters\Expansion;
use Predis\Command\Traits\Bloom_Filters\Max_Iterations;
class CFRESERVE extends Redis_Command
{
    use Bucket_Size {
        Bucket_Size::setArguments as setBucketSize;
    }
    use Max_Iterations {
        Max_Iterations::setArguments as setMaxIterations;
    }
    use Expansion {
        Expansion::setArguments as setExpansion;
    }
    protected static $bucket_size_argument_position_offset = 2;
    protected static $max_iterations_argument_position_offset = 3;
    protected static $expansion_argument_position_offset = 4;
    public function get_id(): string
    {
        return 'CF.RESERVE';
    }
    public function set_arguments(array $arguments): void
    {
        $this->set_expansion($arguments);
        $arguments = $this->get_arguments();
        $this->set_max_iterations($arguments);
        $arguments = $this->get_arguments();
        $this->set_bucket_size($arguments);
        $this->filter_arguments();
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}