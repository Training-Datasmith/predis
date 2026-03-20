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
namespace Predis\Command\Argument\Time_Series;

class Add_Arguments extends Common_Arguments
{
    /**
     * Is overwrite key and database configuration for DUPLICATE_POLICY,
     * the policy for handling samples with identical timestamps.
     *
     * @return $this
     */
    public function on_duplicate(string $policy = self::POLICY_BLOCK): self
    {
        array_push($this->arguments, 'ON_DUPLICATE', $policy);
        return $this;
    }
}