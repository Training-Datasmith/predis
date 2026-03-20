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

class BLMPOP extends LMPOP
{
    protected static $keys_argument_position_offset = 1;
    protected static $left_right_argument_position_offset = 2;
    protected static $count_argument_position_offset = 3;
    public function get_id(): string
    {
        return 'BLMPOP';
    }
}