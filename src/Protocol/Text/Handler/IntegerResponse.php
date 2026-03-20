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
 * Handler for the integer response type in the standard Redis wire protocol.
 * It translates the payload an integer or NULL.
 *
 * @see http://redis.io/topics/protocol
 */
class Integer_Response implements Response_Handler_Interface
{
    /**
     * {@inheritdoc}
     */
    public function handle(Composite_Connection_Interface $connection, $payload)
    {
        if (is_numeric($payload)) {
            $integer = (int) $payload;
            return $integer == $payload ? $integer : $payload;
        }
        if ($payload !== 'nil') {
            Communication_Exception::handle(new Protocol_Exception($connection, "Cannot parse '{$payload}' as a valid numeric response [{$connection->get_parameters()}]"));
        }
    }
}