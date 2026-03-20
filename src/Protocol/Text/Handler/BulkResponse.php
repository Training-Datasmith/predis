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
namespace Predis\Protocol\Text\Handler;

use Predis\Communication_Exception;
use Predis\Connection\Composite_Connection_Interface;
use Predis\Protocol\Protocol_Exception;
/**
 * Handler for the bulk response type in the standard Redis wire protocol.
 * It translates the payload to a string or a NULL.
 *
 * @see http://redis.io/topics/protocol
 */
class Bulk_Response implements Response_Handler_Interface
{
    /**
     * {@inheritdoc}
     */
    public function handle(Composite_Connection_Interface $connection, $payload)
    {
        $length = (int) $payload;
        if ("{$length}" !== $payload) {
            Communication_Exception::handle(new Protocol_Exception($connection, "Cannot parse '{$payload}' as a valid length for a bulk response [{$connection->get_parameters()}]"));
        }
        if ($length >= 0) {
            return substr($connection->read_buffer($length + 2), 0, -2);
        }
        if ($length == -1) {
            return;
        }
        Communication_Exception::handle(new Protocol_Exception($connection, "Value '{$payload}' is not a valid length for a bulk response [{$connection->get_parameters()}]"));
    }
}