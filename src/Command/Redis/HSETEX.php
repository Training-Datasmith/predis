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
class HSETEX extends Redis_Command
{
    public const TTL_NULL = '';
    public const TTL_EX = 'ex';
    public const TTL_PX = 'px';
    public const TTL_EXAT = 'exat';
    public const TTL_PXAT = 'pxat';
    public const TTL_KEEP_TTL = 'keepttl';
    public const SET_NULL = '';
    public const SET_FNX = 'fnx';
    public const SET_FXX = 'fxx';
    /**
     * @var string[]
     */
    private static $ttl_modifier_enum = [self::TTL_EX => 'EX', self::TTL_PX => 'PX', self::TTL_EXAT => 'EXAT', self::TTL_PXAT => 'PXAT', self::TTL_KEEP_TTL => 'KEEPTTL'];
    /**
     * @var string[]
     */
    private static $set_modifier_enum = [self::SET_FNX => 'FNX', self::SET_FXX => 'FXX'];
    public function get_id(): string
    {
        return 'HSETEX';
    }
    public function set_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0]];
        $flat_array = [];
        // Convert key => value, into key, value
        array_walk($arguments[1], static function ($value, $key) use (&$flat_array): void {
            array_push($flat_array, $key, $value);
        });
        // Only required arguments
        if (!array_key_exists(2, $arguments)) {
            array_push($processed_arguments, 'FIELDS', count($flat_array) / 2);
            $processed_arguments = array_merge($processed_arguments, $flat_array);
            parent::set_arguments($processed_arguments);
            return;
        }
        if ($arguments[2] !== '') {
            if (!in_array(strtoupper($arguments[2]), self::$set_modifier_enum)) {
                $enum_values = implode(', ', array_keys(self::$set_modifier_enum));
                throw new UnexpectedValueException("Modifier argument accepts only: {$enum_values} values");
            }
            $processed_arguments[] = self::$set_modifier_enum[strtolower($arguments[2])];
        }
        // Required + set modifier
        if (!array_key_exists(3, $arguments) || $arguments[3] == '') {
            array_push($processed_arguments, 'FIELDS', count($flat_array) / 2);
            $processed_arguments = array_merge($processed_arguments, $flat_array);
            parent::set_arguments($processed_arguments);
            return;
        }
        if (!in_array(strtoupper($arguments[3]), self::$ttl_modifier_enum)) {
            $enum_values = implode(', ', array_keys(self::$ttl_modifier_enum));
            throw new UnexpectedValueException("Modifier argument accepts only: {$enum_values} values");
        }
        // KEEPTTL requires no additional value
        if (strtoupper($arguments[3]) === self::$ttl_modifier_enum[self::TTL_KEEP_TTL]) {
            $processed_arguments[] = self::$ttl_modifier_enum[self::TTL_KEEP_TTL];
            array_push($processed_arguments, 'FIELDS', count($flat_array) / 2);
            $processed_arguments = array_merge($processed_arguments, $flat_array);
            parent::set_arguments($processed_arguments);
            return;
        }
        if (!array_key_exists(4, $arguments) || !is_int($arguments[4])) {
            throw new UnexpectedValueException('Modifier value is missing or incorrect type');
        }
        // Order matters so FIELDS should be at the end
        array_push($processed_arguments, self::$ttl_modifier_enum[strtolower($arguments[3])], $arguments[4], 'FIELDS', count($flat_array) / 2);
        $processed_arguments = array_merge($processed_arguments, $flat_array);
        parent::set_arguments($processed_arguments);
    }
}