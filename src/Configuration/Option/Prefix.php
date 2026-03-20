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

use Predis\Command\Processor\Key_Prefix_Processor;
use Predis\Command\Processor\Processor_Interface;
use Predis\Configuration\Option_Interface;
use Predis\Configuration\Options_Interface;
/**
 * Configures a command processor that apply the specified prefix string to a
 * series of Redis commands considered prefixable.
 */
class Prefix implements Option_Interface
{
    /**
     * {@inheritdoc}
     */
    public function filter(Options_Interface $options, $value)
    {
        if (is_callable($value)) {
            $value = call_user_func($value, $options);
        }
        if ($value instanceof Processor_Interface) {
            return $value;
        }
        return new Key_Prefix_Processor((string) $value);
    }
    /**
     * {@inheritdoc}
     */
    public function get_default(Options_Interface $options): void
    {
        // NOOP
    }
}