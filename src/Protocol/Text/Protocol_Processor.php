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
namespace Predis\Protocol\Text;

use Predis\Command\Command_Interface;
use Predis\Communication_Exception;
use Predis\Connection\Composite_Connection_Interface;
use Predis\Protocol\Protocol_Exception;
use Predis\Protocol\Protocol_Processor_Interface;
use Predis\Response\Error as ErrorResponse;
use Predis\Response\Iterator\Multi_Bulk as MultiBulkIterator;
use Predis\Response\Status as StatusResponse;
/**
 * Protocol processor for the standard Redis wire protocol.
 *
 * @see http://redis.io/topics/protocol
 */
class Protocol_Processor implements Protocol_Processor_Interface
{
    protected $mbiterable;
    protected $serializer;
    public function __construct()
    {
        $this->mbiterable = false;
        $this->serializer = new Request_Serializer();
    }
    /**
     * {@inheritdoc}
     */
    public function write(Composite_Connection_Interface $connection, Command_Interface $command): void
    {
        $request = $this->serializer->serialize($command);
        $connection->write_buffer($request);
    }
    /**
     * {@inheritdoc}
     */
    public function read(Composite_Connection_Interface $connection)
    {
        $chunk = $connection->read_line();
        $prefix = $chunk[0];
        $payload = substr($chunk, 1);
        switch ($prefix) {
            case '+':
                return new Status_Response($payload);
            case '$':
                $size = (int) $payload;
                if ($size === -1) {
                    return;
                }
                return substr($connection->read_buffer($size + 2), 0, -2);
            case '*':
                $count = (int) $payload;
                if ($count === -1) {
                    return;
                }
                if ($this->mbiterable) {
                    return new Multi_Bulk_Iterator($connection, $count);
                }
                $multibulk = [];
                for ($i = 0; $i < $count; ++$i) {
                    $multibulk[$i] = $this->read($connection);
                }
                return $multibulk;
            case ':':
                $integer = (int) $payload;
                return $integer == $payload ? $integer : $payload;
            case '-':
                return new Error_Response($payload);
            default:
                Communication_Exception::handle(new Protocol_Exception($connection, "Unknown response prefix: '{$prefix}' [{$connection->get_parameters()}]"));
                return;
        }
    }
    /**
     * Enables or disables returning multibulk responses as specialized PHP
     * iterators used to stream bulk elements of a multibulk response instead
     * returning a plain array.
     *
     * Streamable multibulk responses are not globally supported by the
     * abstractions built-in into Predis, such as transactions or pipelines.
     * Use them with care!
     *
     * @param bool $value Enable or disable streamable multibulk responses.
     */
    public function use_iterable_multibulk($value): void
    {
        $this->mbiterable = (bool) $value;
    }
}