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
use UnexpectedValueException;
class VADD extends Redis_Command
{
    public const QUANT_DEFAULT = null;
    public const QUANT_NOQUANT = 'NOQUANT';
    public const QUANT_BIN = 'BIN';
    public const QUANT_Q8 = 'Q8';
    /**
     * {@inheritDoc}
     */
    public function get_id(): string
    {
        return 'VADD';
    }
    public function set_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0]];
        if (isset($arguments[3])) {
            array_push($processed_arguments, 'REDUCE', $arguments[3]);
        }
        if (is_string($arguments[1])) {
            array_push($processed_arguments, 'FP32', $arguments[1]);
        } elseif (is_array($arguments[1])) {
            array_push($processed_arguments, 'VALUES', count($arguments[1]), ...$arguments[1]);
        } else {
            throw new UnexpectedValueException('Vector should be rather 32 bit floating blob or array of floatings');
        }
        $processed_arguments[] = $arguments[2];
        if (isset($arguments[4]) && false !== $arguments[4]) {
            $processed_arguments[] = 'CAS';
        }
        if (isset($arguments[5])) {
            $processed_arguments[] = $arguments[5];
        }
        if (isset($arguments[6])) {
            array_push($processed_arguments, 'EF', $arguments[6]);
        }
        if (isset($arguments[7])) {
            $processed_arguments[] = 'SETATTR';
            if (is_string($arguments[7])) {
                $processed_arguments[] = $arguments[7];
            } elseif (is_array($arguments[7])) {
                $processed_arguments[] = json_encode($arguments[7]);
            } else {
                throw new UnexpectedValueException('Attributes arguments should be a JSON string or associative array');
            }
        }
        if (isset($arguments[8])) {
            array_push($processed_arguments, 'M', $arguments[8]);
        }
        parent::set_arguments($processed_arguments);
    }
    public function parse_response($data): bool
    {
        return (bool) $data;
    }
}