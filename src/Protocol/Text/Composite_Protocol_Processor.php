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
use Predis\Connection\Composite_Connection_Interface;
use Predis\Protocol\Protocol_Processor_Interface;
use Predis\Protocol\Request_Serializer_Interface;
use Predis\Protocol\Response_Reader_Interface;
/**
 * Composite protocol processor for the standard Redis wire protocol using
 * pluggable handlers to serialize requests and deserialize responses.
 *
 * @see http://redis.io/topics/protocol
 */
class Composite_Protocol_Processor implements Protocol_Processor_Interface
{
    /*
     * @var RequestSerializerInterface
     */
    protected $serializer;
    /*
     * @var ResponseReaderInterface
     */
    protected $reader;
    /**
     * @param RequestSerializerInterface|null $serializer Request serializer.
     * @param ResponseReaderInterface|null    $reader     Response reader.
     */
    public function __construct(?Request_Serializer_Interface $serializer = null, ?Response_Reader_Interface $reader = null)
    {
        $this->set_request_serializer($serializer ?: new Request_Serializer());
        $this->set_response_reader($reader ?: new Response_Reader());
    }
    /**
     * {@inheritdoc}
     */
    public function write(Composite_Connection_Interface $connection, Command_Interface $command): void
    {
        $connection->write_buffer($this->serializer->serialize($command));
    }
    /**
     * {@inheritdoc}
     */
    public function read(Composite_Connection_Interface $connection)
    {
        return $this->reader->read($connection);
    }
    /**
     * Sets the request serializer used by the protocol processor.
     *
     * @param RequestSerializerInterface $serializer Request serializer.
     */
    public function set_request_serializer(Request_Serializer_Interface $serializer): void
    {
        $this->serializer = $serializer;
    }
    /**
     * Returns the request serializer used by the protocol processor.
     *
     * @return RequestSerializerInterface
     */
    public function get_request_serializer()
    {
        return $this->serializer;
    }
    /**
     * Sets the response reader used by the protocol processor.
     *
     * @param ResponseReaderInterface $reader Response reader.
     */
    public function set_response_reader(Response_Reader_Interface $reader): void
    {
        $this->reader = $reader;
    }
    /**
     * Returns the Response reader used by the protocol processor.
     *
     * @return ResponseReaderInterface
     */
    public function get_response_reader()
    {
        return $this->reader;
    }
}