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
use Predis\Response\Iterator\Multi_Bulk as MultiBulkIterator;
/**
 * Handler for the multibulk response type in the standard Redis wire protocol.
 * It returns multibulk responses as iterators that can stream bulk elements.
 *
 * Streamable multibulk responses are not globally supported by the abstractions
 * built-in into Predis, such as transactions or pipelines. Use them with care!
 *
 * @see http://redis.io/topics/protocol
 */
class Streamable_Multi_Bulk_Response implements Response_Handler_Interface
{
    /**
     * {@inheritdoc}
     */
    public function handle(Composite_Connection_Interface $connection, $payload): \Predis\Response\Iterator\Multi_Bulk
    {
        $length = (int) $payload;
        if ("{$length}" != $payload) {
            Communication_Exception::handle(new Protocol_Exception($connection, "Cannot parse '{$payload}' as a valid length for a multi-bulk response [{$connection->get_parameters()}]"));
        }
        return new Multi_Bulk_Iterator($connection, $length);
    }
}