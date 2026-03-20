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

use Predis\Command\Command as RedisCommand;
/**
 * @see http://redis.io/commands/pubsub
 */
class PUBSUB extends Redis_Command
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'PUBSUB';
    }
    /**
     * {@inheritdoc}
     */
    public function parse_response($data)
    {
        switch (strtolower($this->get_argument(0))) {
            case 'numsub':
                return self::process_numsub($data);
            default:
                return $data;
        }
    }
    /**
     * Returns the processed response to PUBSUB NUMSUB.
     *
     * @param array $channels List of channels
     */
    protected static function process_numsub(array $channels): array
    {
        $processed = [];
        $count = count($channels);
        for ($i = 0; $i < $count; ++$i) {
            $processed[$channels[$i]] = $channels[++$i];
        }
        return $processed;
    }
}