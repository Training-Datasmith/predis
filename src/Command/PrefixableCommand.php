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
namespace Predis\Command;

abstract class Prefixable_Command extends Command implements Prefixable_Command_Interface
{
    /**
     * {@inheritDoc}
     */
    abstract public function get_id();
    /**
     * {@inheritDoc}
     */
    abstract public function prefix_keys($prefix);
    /**
     * Applies prefix for all arguments.
     */
    public function apply_prefix_for_all_arguments(string $prefix): void
    {
        $this->set_raw_arguments(array_map(static function (string $key) use ($prefix): string {
            return $prefix . $key;
        }, $this->get_arguments()));
    }
    /**
     * Applies prefix for first argument.
     */
    public function apply_prefix_for_first_argument(string $prefix): void
    {
        $arguments = $this->get_arguments();
        $arguments[0] = $prefix . $arguments[0];
        $this->set_raw_arguments($arguments);
    }
    /**
     * Applies prefix for interleaved arguments.
     */
    public function apply_prefix_for_interleaved_argument(string $prefix): void
    {
        if ($arguments = $this->get_arguments()) {
            $length = count($arguments);
            for ($i = 0; $i < $length; $i += 2) {
                $arguments[$i] = "{$prefix}{$arguments[$i]}";
            }
            $this->set_raw_arguments($arguments);
        }
    }
    /**
     * Applies prefix for all keys except last one.
     */
    public function apply_prefix_skipping_last_argument(string $prefix): void
    {
        if ($arguments = $this->get_arguments()) {
            $length = count($arguments);
            for ($i = 0; $i < $length - 1; ++$i) {
                $arguments[$i] = "{$prefix}{$arguments[$i]}";
            }
            $this->set_raw_arguments($arguments);
        }
    }
    /**
     * Applies prefix for all keys except first one.
     */
    public function apply_prefix_skipping_first_argument(string $prefix): void
    {
        if ($arguments = $this->get_arguments()) {
            $length = count($arguments);
            for ($i = 1; $i < $length; ++$i) {
                $arguments[$i] = "{$prefix}{$arguments[$i]}";
            }
            $this->set_raw_arguments($arguments);
        }
    }
}