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

/**
 * @see http://redis.io/commands/zrangebyscore
 */
class ZRANGEBYSCORE extends ZRANGE
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'ZRANGEBYSCORE';
    }
    /**
     * {@inheritdoc}
     */
    protected function prepare_options($options): array
    {
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalized_opts = [];
        if (isset($opts['LIMIT']) && is_array($opts['LIMIT'])) {
            $limit = array_change_key_case($opts['LIMIT'], CASE_UPPER);
            $finalized_opts[] = 'LIMIT';
            $finalized_opts[] = $limit['OFFSET'] ?? $limit[0];
            $finalized_opts[] = $limit['COUNT'] ?? $limit[1];
        }
        return array_merge($finalized_opts, parent::prepare_options($options));
    }
    /**
     * {@inheritdoc}
     */
    protected function with_scores(): bool
    {
        $arguments = $this->get_arguments();
        for ($i = 3; $i < count($arguments); ++$i) {
            switch (strtoupper($arguments[$i])) {
                case 'WITHSCORES':
                    return true;
                case 'LIMIT':
                    $i += 2;
                    break;
            }
        }
        return false;
    }
}