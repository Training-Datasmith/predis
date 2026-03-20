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
namespace Predis\Pipeline;

use Predis\Communication_Exception;
use Predis\Connection\Aggregate_Connection_Interface;
use Predis\Connection\Connection_Interface;
use SplQueue;
use Throwable;
/**
 * Command pipeline that writes commands to the servers but discards responses.
 */
class Fire_And_Forget extends Pipeline
{
    /**
     * {@inheritdoc}
     */
    protected function execute_pipeline(Connection_Interface $connection, SplQueue $commands): array
    {
        $retry = $connection->get_parameters()->retry;
        $retry->call_with_retry(function () use ($connection, $commands): void {
            if ($connection instanceof Aggregate_Connection_Interface) {
                $this->write_to_multi_node($connection, $commands);
            } else {
                $this->write_to_single_node($connection, $commands);
            }
        }, static function (Throwable $e): void {
            if ($e instanceof Communication_Exception) {
                $e->get_connection()->disconnect();
            }
        });
        $connection->disconnect();
        return [];
    }
}