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
namespace Predis\Command\Traits\By;

use Predis\Command\Command;
use UnexpectedValueException;
/**
 * @mixin Command
 */
trait By_Lex_By_Score
{
    private static $arguments_enum = ['bylex' => 'BYLEX', 'byscore' => 'BYSCORE'];
    public function set_arguments(array $arguments): void
    {
        if (count($arguments) <= static::$by_lex_by_score_argument_position_offset || false === $arguments[static::$by_lex_by_score_argument_position_offset]) {
            parent::set_arguments($arguments);
            return;
        }
        $argument = $arguments[static::$by_lex_by_score_argument_position_offset];
        if (is_string($argument) && in_array(strtoupper($argument), self::$arguments_enum)) {
            $argument = self::$arguments_enum[$argument];
        } else {
            throw new UnexpectedValueException('By argument accepts only "bylex" and "byscore" values');
        }
        $arguments_before = array_slice($arguments, 0, static::$by_lex_by_score_argument_position_offset);
        $arguments_after = array_slice($arguments, static::$by_lex_by_score_argument_position_offset + 1);
        parent::set_arguments(array_merge($arguments_before, [$argument], $arguments_after));
    }
}