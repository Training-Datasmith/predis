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
use UnexpectedValueException;
class HEXPIRE extends Redis_Command
{
    /**
     * @var array
     */
    protected $flags_enum = ['NX', 'XX', 'GT', 'LT'];
    public function get_id(): string
    {
        return 'HEXPIRE';
    }
    public function set_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0], $arguments[1]];
        if (array_key_exists(3, $arguments) && null !== $arguments[3]) {
            if (in_array(strtoupper($arguments[3]), $this->flags_enum, true)) {
                $processed_arguments[] = strtoupper($arguments[3]);
            } else {
                throw new UnexpectedValueException('Unsupported flag value');
            }
        }
        if (array_key_exists(2, $arguments) && null !== $arguments[2]) {
            array_push($processed_arguments, 'FIELDS', count($arguments[2]));
            $processed_arguments = array_merge($processed_arguments, $arguments[2]);
        }
        parent::set_arguments($processed_arguments);
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}