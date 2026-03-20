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

use Predis\Communication_Exception;
use Predis\Connection\Composite_Connection_Interface;
use Predis\Protocol\Protocol_Exception;
use Predis\Protocol\Response_Reader_Interface;
/**
 * Response reader for the standard Redis wire protocol.
 *
 * @see http://redis.io/topics/protocol
 */
class Response_Reader implements Response_Reader_Interface
{
    protected $handlers;
    public function __construct()
    {
        $this->handlers = $this->get_default_handlers();
    }
    /**
     * Returns the default handlers for the supported type of responses.
     */
    protected function get_default_handlers(): array
    {
        return ['+' => new Handler\Status_Response(), '-' => new Handler\Error_Response(), ':' => new Handler\Integer_Response(), '$' => new Handler\Bulk_Response(), '*' => new Handler\Multi_Bulk_Response()];
    }
    /**
     * Sets the handler for the specified prefix identifying the response type.
     *
     * @param string                           $prefix  Identifier of the type of response.
     * @param Handler\ResponseHandlerInterface $handler Response handler.
     */
    public function set_handler($prefix, Handler\Response_Handler_Interface $handler): void
    {
        $this->handlers[$prefix] = $handler;
    }
    /**
     * Returns the response handler associated to a certain type of response.
     *
     * @param string $prefix Identifier of the type of response.
     *
     * @return Handler\ResponseHandlerInterface|void
     */
    public function get_handler($prefix)
    {
        if (isset($this->handlers[$prefix])) {
            return $this->handlers[$prefix];
        }
    }
    /**
     * {@inheritdoc}
     */
    public function read(Composite_Connection_Interface $connection)
    {
        $header = $connection->read_line();
        if ($header === '') {
            $this->on_protocol_error($connection, 'Unexpected empty response header');
        }
        $prefix = $header[0];
        if (!isset($this->handlers[$prefix])) {
            $this->on_protocol_error($connection, "Unknown response prefix: '{$prefix}'");
        }
        return $this->handlers[$prefix]->handle($connection, substr($header, 1));
    }
    /**
     * Handles protocol errors generated while reading responses from a
     * connection.
     *
     * @param CompositeConnectionInterface $connection Redis connection that generated the error.
     * @param string                       $message    Error message.
     */
    protected function on_protocol_error(Composite_Connection_Interface $connection, $message)
    {
        Communication_Exception::handle(new Protocol_Exception($connection, "{$message} [{$connection->get_parameters()}]"));
    }
}