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
/**
 * @see http://redis.io/commands/sort
 */
class SORT extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'SORT';
    }
    /**
     * {@inheritdoc}
     */
    public function set_arguments(array $arguments): void
    {
        if (count($arguments) === 1) {
            parent::set_arguments($arguments);
            return;
        }
        $query = [$arguments[0]];
        $sort_params = array_change_key_case($arguments[1], CASE_UPPER);
        if (isset($sort_params['BY'])) {
            $query[] = 'BY';
            $query[] = $sort_params['BY'];
        }
        if (isset($sort_params['GET'])) {
            $getargs = $sort_params['GET'];
            if (is_array($getargs)) {
                foreach ($getargs as $getarg) {
                    $query[] = 'GET';
                    $query[] = $getarg;
                }
            } else {
                $query[] = 'GET';
                $query[] = $getargs;
            }
        }
        if (isset($sort_params['LIMIT']) && is_array($sort_params['LIMIT']) && count($sort_params['LIMIT']) == 2) {
            $query[] = 'LIMIT';
            $query[] = $sort_params['LIMIT'][0];
            $query[] = $sort_params['LIMIT'][1];
        }
        if (isset($sort_params['SORT'])) {
            $query[] = strtoupper($sort_params['SORT']);
        }
        if (isset($sort_params['ALPHA']) && $sort_params['ALPHA'] == true) {
            $query[] = 'ALPHA';
        }
        if (isset($sort_params['STORE'])) {
            $query[] = 'STORE';
            $query[] = $sort_params['STORE'];
        }
        parent::set_arguments($query);
    }
    public function prefix_keys($prefix): void
    {
        if ($arguments = $this->get_arguments()) {
            $arguments[0] = "{$prefix}{$arguments[0]}";
            if (($count = count($arguments)) > 1) {
                for ($i = 1; $i < $count; ++$i) {
                    switch (strtoupper($arguments[$i])) {
                        case 'BY':
                        case 'STORE':
                            $arguments[$i] = "{$prefix}{$arguments[++$i]}";
                            break;
                        case 'GET':
                            $value = $arguments[++$i];
                            if ($value !== '#') {
                                $arguments[$i] = "{$prefix}{$value}";
                            }
                            break;
                        case 'LIMIT':
                            $i += 2;
                            break;
                    }
                }
            }
            $this->set_raw_arguments($arguments);
        }
    }
}