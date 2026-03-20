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
namespace Predis\Command\Traits;

use Predis\Command\Command;
use UnexpectedValueException;
/**
 * @mixin Command
 */
trait Min_Max_Modifier
{
    /**
     * @var array{string: string}
     */
    private $modifier_enum = ['min' => 'MIN', 'max' => 'MAX'];
    public function resolve_modifier(int $offset, array &$arguments): void
    {
        if ($offset >= count($arguments)) {
            $arguments[$offset] = $this->modifier_enum['min'];
            return;
        }
        if (!is_string($arguments[$offset]) || !array_key_exists($arguments[$offset], $this->modifier_enum)) {
            throw new UnexpectedValueException('Wrong type of modifier given');
        }
        $arguments[$offset] = $this->modifier_enum[$arguments[$offset]];
    }
}