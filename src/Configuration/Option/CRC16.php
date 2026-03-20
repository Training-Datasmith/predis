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
use Predis\Cluster\Hash;
use Predis\Configuration\Option_Interface;
use Predis\Configuration\Options_Interface;
/**
 * Configures an hash generator used by the redis-cluster connection backend.
 */
class CRC16 implements Option_Interface
{
    /**
     * Returns an hash generator instance from a descriptive name.
     *
     * @param OptionsInterface $options     Client options.
     * @param string           $description Identifier of a hash generator (`predis`)
     *
     * @return callable
     */
    protected function get_hash_generator_by_description(Options_Interface $options, $description): \Predis\Cluster\Hash\CRC16
    {
        if ($description === 'predis') {
            return new Hash\CRC16();
        }
        throw new InvalidArgumentException('String value for the crc16 option must be either `predis`');
    }
    /**
     * {@inheritdoc}
     */
    public function filter(Options_Interface $options, $value)
    {
        if (is_callable($value)) {
            $value = call_user_func($value, $options);
        }
        if (is_string($value)) {
            return $this->get_hash_generator_by_description($options, $value);
        }
        if ($value instanceof Hash\Hash_Generator_Interface) {
            return $value;
        }
        $class = get_class($this);
        throw new InvalidArgumentException("{$class} expects a valid hash generator");
    }
    /**
     * {@inheritdoc}
     */
    public function get_default(Options_Interface $options): \Predis\Cluster\Hash\CRC16
    {
        return new Hash\CRC16();
    }
}