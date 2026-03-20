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
namespace Predis\Connection;

use InvalidArgumentException;
use Predis\Command\Command_Interface;
use Predis\Protocol\Protocol_Processor_Interface;
use Predis\Protocol\Text\Protocol_Processor as TextProtocolProcessor;
use Psr\Http\Message\Stream_Interface;
use RuntimeException;
/**
 * Connection abstraction to Redis servers based on PHP's stream that uses an
 * external protocol processor defining the protocol used for the communication.
 *
 * @method StreamInterface getResource()
 */
class Composite_Stream_Connection extends Stream_Connection implements Composite_Connection_Interface
{
    protected $protocol;
    /**
     * @param ParametersInterface             $parameters Initialization parameters for the connection.
     * @param ProtocolProcessorInterface|null $protocol   Protocol processor.
     */
    public function __construct(Parameters_Interface $parameters, ?Protocol_Processor_Interface $protocol = null)
    {
        parent::__construct($parameters);
        $this->protocol = $protocol ?: new Text_Protocol_Processor();
    }
    /**
     * {@inheritdoc}
     */
    public function get_protocol()
    {
        return $this->protocol;
    }
    /**
     * {@inheritdoc}
     */
    public function write_buffer($buffer): void
    {
        $this->write($buffer);
    }
    /**
     * {@inheritdoc}
     */
    public function read_buffer($length): string
    {
        if ($length <= 0) {
            throw new InvalidArgumentException('Length parameter must be greater than 0.');
        }
        $value = '';
        $stream = $this->get_resource();
        if ($stream->eof()) {
            $this->on_stream_error(new RuntimeException('Stream is already at the end'), '');
        }
        do {
            try {
                $chunk = $stream->read($length);
            } catch (RuntimeException $e) {
                $this->on_stream_error($e, 'Error while reading bytes from the server.');
            }
            $value .= $chunk;
            // @phpstan-ignore-line
        } while (($length -= strlen($chunk)) > 0);
        // @phpstan-ignore-line
        return $value;
    }
    /**
     * {@inheritdoc}
     */
    public function read_line(): string
    {
        $value = '';
        $stream = $this->get_resource();
        if ($stream->eof()) {
            $this->on_stream_error(new RuntimeException('Stream is already at the end'), '');
        }
        do {
            try {
                $chunk = $stream->read(-1);
            } catch (RuntimeException $e) {
                $this->on_stream_error($e, 'Error while reading bytes from the server.');
            }
            $value .= $chunk;
            // @phpstan-ignore-line
        } while (substr($value, -2) !== "\r\n");
        return substr($value, 0, -2);
    }
    /**
     * {@inheritdoc}
     */
    public function write_request(Command_Interface $command): void
    {
        $this->protocol->write($this, $command);
    }
    /**
     * {@inheritdoc}
     */
    public function read()
    {
        return $this->protocol->read($this);
    }
    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return array_merge(parent::__sleep(), ['protocol']);
    }
}