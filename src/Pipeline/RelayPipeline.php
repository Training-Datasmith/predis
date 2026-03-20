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

use Predis\Connection\Connection_Interface;
use Predis\Connection\Relay_Connection;
use Predis\Response\Error;
use Predis\Response\Server_Exception;
use Relay\Exception as RelayException;
use SplQueue;
class Relay_Pipeline extends Pipeline
{
    /**
     * Implements the logic to flush the queued commands and read the responses
     * from the current connection.
     *
     * @param  RelayConnection $connection Current connection instance.
     * @param  SplQueue        $commands   Queued commands.
     * @return array
     */
    protected function execute_pipeline(Connection_Interface $connection, SplQueue $commands)
    {
        /** @var RelayConnection $connection */
        $client = $connection->get_client();
        $throw = $this->client->get_options()->exceptions;
        try {
            $pipeline = $client->pipeline();
            foreach ($commands as $command) {
                $name = $command->get_id();
                in_array($name, $connection->atypical_commands) ? $pipeline->{$name}(...$command->get_arguments()) : $pipeline->raw_command($name, ...$command->get_arguments());
            }
            $responses = $pipeline->exec();
            if (!is_array($responses)) {
                return $responses;
            }
            foreach ($responses as $key => $response) {
                if ($response instanceof Relay_Exception) {
                    if ($throw) {
                        throw $response;
                    }
                    $responses[$key] = new Error($response->get_message());
                }
            }
            return $responses;
        } catch (Relay_Exception $ex) {
            if ($client->get_mode() !== $client::ATOMIC) {
                $client->discard();
            }
            throw new Server_Exception($ex->get_message(), $ex->get_code(), $ex);
        }
    }
}