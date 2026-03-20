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
namespace Predis\Configuration\Option;

use Predis\Configuration\Option_Interface;
use Predis\Configuration\Options_Interface;
/**
 * Configures whether consumers (such as the client) should throw exceptions on
 * Redis errors (-ERR responses) or just return instances of error responses.
 */
class Exceptions implements Option_Interface
{
    /**
     * {@inheritdoc}
     */
    public function filter(Options_Interface $options, $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    /**
     * {@inheritdoc}
     */
    public function get_default(Options_Interface $options): bool
    {
        return true;
    }
}