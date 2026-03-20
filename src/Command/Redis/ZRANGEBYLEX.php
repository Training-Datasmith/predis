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
 * @see http://redis.io/commands/zrangebylex
 */
class ZRANGEBYLEX extends ZRANGE
{
    /**
     * {@inheritdoc}
     */
    public function get_id(): string
    {
        return 'ZRANGEBYLEX';
    }
    /**
     * {@inheritdoc}
     * @return list<mixed>
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
        return $finalized_opts;
    }
    /**
     * {@inheritdoc}
     */
    protected function with_scores(): bool
    {
        return false;
    }
}