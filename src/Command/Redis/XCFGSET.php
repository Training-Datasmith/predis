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
 * @see http://redis.io/commands/xcfgset
 *
 * XCFGSET key [IDMP-DURATION duration] [IDMP-MAXSIZE maxsize]
 *
 * Configures the idempotency parameters for a stream's IDMP map.
 */
class XCFGSET extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'XCFGSET';
    }
    /**
     * {@inheritdoc}
     */
    public function set_arguments(array $arguments): void
    {
        $processed_arguments = [$arguments[0]];
        // IDMP-DURATION option
        if (isset($arguments[1]) && $arguments[1] !== null) {
            array_push($processed_arguments, 'IDMP-DURATION', $arguments[1]);
        }
        // IDMP-MAXSIZE option
        if (isset($arguments[2]) && $arguments[2] !== null) {
            array_push($processed_arguments, 'IDMP-MAXSIZE', $arguments[2]);
        }
        parent::set_arguments($processed_arguments);
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}