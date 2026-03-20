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

use InvalidArgumentException;
use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see http://redis.io/commands/bitop
 */
class BITOP extends Redis_Command
{
    private const VALID_OPERATIONS = ['AND', 'OR', 'XOR', 'NOT', 'DIFF', 'DIFF1', 'ANDOR', 'ONE'];
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'BITOP';
    }
    /**
     * {@inheritdoc}
     */
    public function set_arguments(array $arguments): void
    {
        if (count($arguments) === 3 && is_array($arguments[2])) {
            [$operation, $destination] = $arguments;
            $arguments = $arguments[2];
            array_unshift($arguments, $operation, $destination);
        }
        if (!empty($arguments)) {
            $operation = strtoupper($arguments[0]);
            if (!in_array($operation, self::VALID_OPERATIONS, false)) {
                throw new InvalidArgumentException('BITOP operation must be one of: AND, OR, XOR, NOT, DIFF, DIFF1, ANDOR, ONE');
            }
        }
        parent::set_arguments($arguments);
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_skipping_first_argument($prefix);
    }
}