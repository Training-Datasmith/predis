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

use Predis\Command\Command_Interface;
use Predis\Command\Raw_Command;
use Predis\Communication_Exception;
use Predis\Connection\Resource\Exception\Stream_Init_Exception;
use Predis\Connection\Resource\Stream_Factory;
use Predis\Connection\Resource\Stream_Factory_Interface;
use Predis\Consumer\Push\Push_Notification_Exception;
use Predis\Consumer\Push\Push_Response;
use Predis\Protocol\Parser\Strategy\Resp2Strategy;
use Predis\Protocol\Parser\Strategy\Resp3Strategy;
use Predis\Protocol\Parser\Unexpected_Type_Exception;
use Predis\Response\Error;
use Predis\Response\Error_Interface as ErrorResponseInterface;
use Psr\Http\Message\Stream_Interface;
use RuntimeException;
/**
 * Standard connection to Redis servers implemented on top of PHP's streams.
 * The connection parameters supported by this class are:.
 *
 *  - scheme: it can be either 'redis', 'tcp', 'rediss', 'tls' or 'unix'.
 *  - host: hostname or IP address of the server.
 *  - port: TCP port of the server.
 *  - path: path of a UNIX domain socket when scheme is 'unix'.
 *  - timeout: timeout to perform the connection (default is 5 seconds).
 *  - read_write_timeout: timeout of read / write operations.
 *  - async_connect: performs the connection asynchronously.
 *  - tcp_nodelay: enables or disables Nagle's algorithm for coalescing.
 *  - persistent: the connection is left intact after a GC collection.
 *  - ssl: context options array (see http://php.net/manual/en/context.ssl.php)
 *
 * @method StreamInterface getResource()
 */
class Stream_Connection extends Abstract_Connection
{
    /**
     * @var StreamFactoryInterface
     */
    protected $stream_factory;
    public function __construct(Parameters_Interface $parameters, ?Stream_Factory_Interface $factory = null)
    {
        parent::__construct($parameters);
        $this->stream_factory = $factory ?? new Stream_Factory();
    }
    /**
     * Disconnects from the server and destroys the underlying resource when the
     * garbage collector kicks in only if the connection has not been marked as
     * persistent.
     */
    public function __destruct()
    {
        if (isset($this->parameters->persistent) && $this->parameters->persistent) {
            return;
        }
        $this->disconnect();
    }
    /**
     * {@inheritdoc}
     */
    protected function create_resource(): Stream_Interface
    {
        return $this->stream_factory->create_stream($this->parameters);
    }
    /**
     * {@inheritdoc}
     */
    public function connect(): void
    {
        if (parent::connect() && $this->init_commands) {
            $responses = $this->send_pipeline($this->init_commands);
            if ($responses[0][0] instanceof Error_Response_Interface) {
                // Error in HELLO command, Redis < 6.0.
                // We need to handle it separately and re-send other commands.
                $this->handle_on_connect_response($responses[0][0], $responses[0][1]);
                $responses = $this->send_pipeline(array_slice($this->init_commands, 1));
            }
            foreach ($responses as $response) {
                $this->handle_on_connect_response($response[0], $response[1]);
            }
        }
    }
    /**
     * Sends commands to the server as pipeline and returns responses.
     *
     * @param  CommandInterface[]     $commands
     * @return array<int, array>
     * @throws CommunicationException
     */
    protected function send_pipeline(array $commands): array
    {
        $serialised_commands = '';
        foreach ($commands as $command) {
            $serialised_commands .= $command->serialize_command();
        }
        $this->write($serialised_commands);
        $responses = [];
        foreach ($commands as $command) {
            $responses[] = [$this->read_response($command), $command];
        }
        return $responses;
    }
    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        if ($this->is_connected()) {
            $this->get_resource()->close();
            parent::disconnect();
        }
    }
    /**
     * {@inheritDoc}
     * @throws CommunicationException
     */
    public function write(string $buffer): void
    {
        $stream = $this->get_resource();
        while (($length = strlen($buffer)) > 0) {
            try {
                $written = $stream->write($buffer);
            } catch (RuntimeException $e) {
                $this->on_stream_error($e, 'Error while writing bytes to the server.');
            }
            if ($length === $written) {
                // @phpstan-ignore-line
                return;
            }
            $buffer = substr($buffer, $written);
            // @phpstan-ignore-line
        }
    }
    /**
     * {@inheritdoc}
     * @throws PushNotificationException
     * @throws StreamInitException|CommunicationException
     */
    public function read()
    {
        $stream = $this->get_resource();
        if ($stream->eof()) {
            $this->on_stream_error(new RuntimeException('', 1), 'Stream is already at the end');
        }
        try {
            $chunk = $stream->read(-1);
        } catch (RuntimeException $e) {
            $this->on_stream_error($e, 'Error while reading line from the server.');
        }
        try {
            $parsed_data = $this->parser_strategy->parse_data($chunk);
            // @phpstan-ignore-line
        } catch (Unexpected_Type_Exception $e) {
            $this->on_protocol_error("Unknown response prefix: '{$e->get_type()}'.");
            return;
        }
        if (!is_array($parsed_data)) {
            return $parsed_data;
        }
        switch ($parsed_data['type']) {
            case Resp3Strategy::TYPE_PUSH:
                $data = [];
                for ($i = 0; $i < $parsed_data['value']; ++$i) {
                    $data[$i] = $this->read();
                }
                return new Push_Response($data);
            case Resp2Strategy::TYPE_ARRAY:
                $data = [];
                for ($i = 0; $i < $parsed_data['value']; ++$i) {
                    $data[$i] = $this->read();
                }
                return $data;
            case Resp2Strategy::TYPE_BULK_STRING:
                $bulk_data = $this->read_by_chunks($stream, $parsed_data['value']);
                return substr($bulk_data, 0, -2);
            case Resp3Strategy::TYPE_VERBATIM_STRING:
                $bulk_data = $this->read_by_chunks($stream, $parsed_data['value']);
                return substr($bulk_data, $parsed_data['offset'], -2);
            case Resp3Strategy::TYPE_BLOB_ERROR:
                $error_message = $this->read_by_chunks($stream, $parsed_data['value']);
                return new Error(substr($error_message, 0, -2));
            case Resp3Strategy::TYPE_MAP:
                $data = [];
                for ($i = 0; $i < $parsed_data['value']; ++$i) {
                    $key = $this->read();
                    $data[$key] = $this->read();
                }
                return $data;
            case Resp3Strategy::TYPE_SET:
                $data = [];
                for ($i = 0; $i < $parsed_data['value']; ++$i) {
                    $element = $this->read();
                    if (!in_array($element, $data, true)) {
                        $data[] = $element;
                    }
                }
                return $data;
        }
        return $parsed_data;
    }
    /**
     * {@inheritdoc}
     */
    public function write_request(Command_Interface $command): void
    {
        $buffer = $command->serialize_command();
        $this->write($buffer);
    }
    /**
     * {@inheritDoc}
     */
    public function has_data_to_read(): bool
    {
        return !$this->get_resource()->eof();
    }
    /**
     * Reads given resource split on chunks with given size.
     *
     * @throws CommunicationException
     */
    private function read_by_chunks(Stream_Interface $stream, int $chunk_size): string
    {
        $string = '';
        $bytes_left = $chunk_size += 2;
        do {
            try {
                $chunk = $stream->read(min($bytes_left, 4096));
            } catch (RuntimeException $e) {
                $this->on_stream_error($e, 'Error while reading bytes from the server.');
            }
            $string .= $chunk;
            // @phpstan-ignore-line
            $bytes_left = $chunk_size - strlen($string);
        } while ($bytes_left > 0);
        return $string;
    }
    /**
     * Handle response from on-connect command.
     *
     * @param                         $response
     * @throws CommunicationException
     */
    private function handle_on_connect_response($response, Command_Interface $command): void
    {
        if ($response instanceof Error_Response_Interface) {
            $this->handle_error($response, $command);
        }
        if ($command->get_id() === 'HELLO' && is_array($response)) {
            // Searching for the CLIENT ID in RESP2 connection tricky because no dictionaries.
            if ($this->get_parameters()->protocol == 2 && false !== $key = array_search('id', $response, true)) {
                $this->client_id = $response[$key + 1];
            } elseif ($this->get_parameters()->protocol == 3) {
                $this->client_id = $response['id'];
            }
        }
    }
    /**
     * Handle server errors.
     *
     * @throws CommunicationException
     */
    private function handle_error(Error_Response_Interface $error, Command_Interface $failed_command): void
    {
        if ($failed_command->get_id() === 'CLIENT') {
            // Do nothing on CLIENT SETINFO command failure
            return;
        }
        if ($failed_command->get_id() === 'HELLO') {
            if (in_array('AUTH', $failed_command->get_arguments(), true)) {
                $parameters = $this->get_parameters();
                // If Redis <= 6.0
                $auth = new Raw_Command('AUTH', [$parameters->password]);
                $response = $this->execute_command($auth);
                if ($response instanceof Error_Response_Interface) {
                    $this->on_connection_error("Failed: {$response->get_message()}");
                }
            }
            $set_name = new Raw_Command('CLIENT', ['SETNAME', 'predis']);
            $response = $this->execute_command($set_name);
            $this->handle_on_connect_response($response, $set_name);
            return;
        }
        $this->on_connection_error("Failed: {$error->get_message()}");
    }
    /**
     * Handles stream-related exceptions.
     *
     * @param  RuntimeException                        $e
     * @throws RuntimeException|CommunicationException
     */
    protected function on_stream_error($e, ?string $message = null)
    {
        // Code = 1 represents issues related to read/write operation, connection broken.
        if ($e->get_code() === 1) {
            $this->on_connection_error($message);
        } elseif ($e->get_code() === 2) {
            // Operation has been timed out, connection not necessarily broken.
            $this->on_timeout_error();
        }
        throw $e;
    }
}