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
namespace Predis\Command\Redis\Cuckoo_Filter;

/**
 * @see https://redis.io/commands/cf.insertnx/
 *
 * Adds one or more items to a cuckoo filter, allowing the filter
 * to be created with a custom capacity if it does not exist yet.
 */
class CFINSERTNX extends CFINSERT
{
    public function get_id(): string
    {
        return 'CF.INSERTNX';
    }
}