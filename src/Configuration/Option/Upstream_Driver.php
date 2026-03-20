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

use InvalidArgumentException;
use Predis\Configuration\Option_Interface;
use Predis\Configuration\Options_Interface;
/**
 * Configures upstream driver information for CLIENT SETINFO.
 *
 * This option accepts a string or array of strings identifying upstream drivers
 * (e.g., 'laravel_v11.0.0' or ['laravel_v11.0.0', 'my-app_v1.0.0']) that will
 * be included in the LIB-NAME sent to Redis via CLIENT SETINFO.
 */
class Upstream_Driver implements Option_Interface
{
    /**
     * {@inheritdoc}
     */
    public function filter(Options_Interface $options, $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value)) {
            return implode(';', $value);
        }
        throw new InvalidArgumentException('UpstreamDriver option expects a string or an array of strings');
    }
    /**
     * {@inheritdoc}
     */
    public function get_default(Options_Interface $options): string
    {
        return '';
    }
}