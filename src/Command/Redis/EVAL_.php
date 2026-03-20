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

use Predis\Command\Prefixable_Command as RedisCommand;
/**
 * @see http://redis.io/commands/eval
 */
class EVAL_ extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'EVAL';
    }
    /**
     * Calculates the SHA1 hash of the body of the script.
     *
     * @return string SHA1 hash.
     */
    public function get_script_hash(): string
    {
        return sha1($this->get_argument(0));
    }
    public function prefix_keys($prefix): void
    {
        if ($arguments = $this->get_arguments()) {
            for ($i = 2; $i < $arguments[1] + 2; ++$i) {
                $arguments[$i] = "{$prefix}{$arguments[$i]}";
            }
            $this->set_raw_arguments($arguments);
        }
    }
}