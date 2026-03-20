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
use Predis\Command\Traits\Aggregate;
use Predis\Command\Traits\Keys;
use Predis\Command\Traits\Weights;
/**
 * @see http://redis.io/commands/zunionstore
 */
class ZUNIONSTORE extends Redis_Command
{
    use Keys {
        Keys::setArguments as setKeys;
    }
    use Weights {
        Weights::setArguments as setWeights;
    }
    use Aggregate {
        Aggregate::setArguments as setAggregate;
    }
    protected static $keys_argument_position_offset = 1;
    protected static $weights_argument_position_offset = 2;
    protected static $aggregate_argument_position_offset = 3;
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'ZUNIONSTORE';
    }
    /**
     * {@inheritdoc}
     */
    public function set_arguments(array $arguments): void
    {
        // support old `$options` array for backwards compatibility
        if (!isset($arguments[3]) && (isset($arguments[2]['weights']) || isset($arguments[2]['aggregate']))) {
            $options = array_pop($arguments);
            array_push($arguments, $options['weights'] ?? []);
            array_push($arguments, $options['aggregate'] ?? 'sum');
        }
        $this->set_aggregate($arguments);
        $arguments = $this->get_arguments();
        $this->set_weights($arguments);
        $arguments = $this->get_arguments();
        $this->set_keys($arguments);
    }
    public function prefix_keys($prefix): void
    {
        if ($arguments = $this->get_arguments()) {
            $arguments[0] = "{$prefix}{$arguments[0]}";
            $length = (int) $arguments[1] + 2;
            for ($i = 2; $i < $length; ++$i) {
                $arguments[$i] = "{$prefix}{$arguments[$i]}";
            }
            $this->set_raw_arguments($arguments);
        }
    }
}