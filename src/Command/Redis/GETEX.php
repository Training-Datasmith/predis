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
class GETEX extends Redis_Command
{
    /**
     * @var string[]
     */
    private static $modifier_enum = ['ex' => 'EX', 'px' => 'PX', 'exat' => 'EXAT', 'pxat' => 'PXAT', 'persist' => 'PERSIST'];
    public function get_id(): string
    {
        return 'GETEX';
    }
    public function set_arguments(array $arguments): void
    {
        if (!array_key_exists(1, $arguments) || $arguments[1] === '') {
            parent::set_arguments([$arguments[0]]);
            return;
        }
        if (!in_array(strtoupper($arguments[1]), self::$modifier_enum)) {
            $enum_values = implode(', ', array_keys(self::$modifier_enum));
            throw new UnexpectedValueException("Modifier argument accepts only: {$enum_values} values");
        }
        if ($arguments[1] === 'persist') {
            parent::set_arguments([$arguments[0], self::$modifier_enum[$arguments[1]]]);
            return;
        }
        $arguments[1] = self::$modifier_enum[$arguments[1]];
        if (!array_key_exists(2, $arguments)) {
            throw new UnexpectedValueException('You should provide value for current modifier');
        }
        parent::set_arguments($arguments);
    }
}