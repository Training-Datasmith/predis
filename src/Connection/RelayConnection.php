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
use Predis\Client_Exception;
use Predis\Command\Command_Interface;
use Predis\Not_Supported_Exception;
use Predis\Response\Error_Interface as ErrorResponseInterface;
use Predis\Response\Server_Exception;
use Relay\Exception as RelayException;
use Relay\Relay;
/**
 * This class provides the implementation of a Predis connection that
 * uses Relay for network communication and in-memory caching.
 *
 * Using Relay allows for:
 * 1) significantly faster reads thanks to in-memory caching
 * 2) fast data serialization using igbinary
 * 3) fast data compression using lzf, lz4 or zstd
 *
 * Usage of igbinary serialization and zstd compresses reduces
 * network traffic and Redis memory usage by ~75%.
 *
 * For instructions on how to install the Relay extension, please consult
 * the repository of the project: https://relay.so/docs/installation
 *
 * The connection parameters supported by this class are:
 *
 *  - scheme: it can be either 'tcp', 'tls' or 'unix'.
 *  - host: hostname or IP address of the server.
 *  - port: TCP port of the server.
 *  - path: path of a UNIX domain socket when scheme is 'unix'.
 *  - timeout: timeout to perform the connection.
 *  - read_write_timeout: timeout of read / write operations.
 *  - cache: whether to use in-memory caching
 *  - serializer: data serializer
 *  - compression: data compression algorithm
 *
 * @see https://github.com/cachewerk/relay
 */
class Relay_Connection extends Abstract_Connection
{
    use Relay_Methods;
    /**
     * The Relay instance.
     *
     * @var Relay
     */
    protected $client;
    /**
     * These commands must be called on the client, not using `Relay::rawCommand()`.
     *
     * @var string[]
     */
    public $atypical_commands = ['AUTH', 'SELECT', 'TYPE', 'MULTI', 'EXEC', 'DISCARD', 'WATCH', 'UNWATCH', 'SUBSCRIBE', 'UNSUBSCRIBE', 'PSUBSCRIBE', 'PUNSUBSCRIBE', 'SSUBSCRIBE', 'SUNSUBSCRIBE'];
    /**
     * {@inheritdoc}
     */
    public function __construct(Parameters_Interface $parameters, Relay $client)
    {
        $this->assert_extensions();
        $this->parameters = $this->assert_parameters($parameters);
        $this->client = $client;
    }
    /**
     * {@inheritdoc}
     */
    public function is_connected(): bool
    {
        return $this->client->is_connected();
    }
    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        if ($this->client->is_connected()) {
            $this->client->close();
        }
    }
    /**
     * Checks if the Relay extension is loaded in PHP.
     */
    private function assert_extensions(): void
    {
        if (!extension_loaded('relay')) {
            throw new Not_Supported_Exception('The "relay" extension is required by this connection backend.');
        }
    }
    /**
     * Returns the underlying client.
     *
     * @return Relay
     */
    public function get_client()
    {
        return $this->client;
    }
    /**
     * @param                      $address
     * @param                      $flags
     * @return Relay
     */
    protected function connect_with_configuration(Parameters_Interface $parameters, $address, $flags)
    {
        $timeout = isset($parameters->timeout) ? (float) $parameters->timeout : 5.0;
        $retry_interval = 0;
        $read_timeout = 5.0;
        if (isset($parameters->read_write_timeout)) {
            $read_timeout = (float) $parameters->read_write_timeout;
            $read_timeout = $read_timeout > 0 ? $read_timeout : 0;
        }
        try {
            $this->client->connect($parameters->path ?? $parameters->host, isset($parameters->path) ? 0 : $parameters->port, $timeout, null, $retry_interval, $read_timeout);
        } catch (Relay_Exception $ex) {
            $this->on_connection_error($ex->get_message(), $ex->get_code());
        }
        return $this->client;
    }
    /**
     * {@inheritdoc}
     */
    public function get_identifier()
    {
        try {
            return $this->client->endpoint_id();
        } catch (Relay_Exception $ex) {
            return parent::get_identifier();
        }
    }
    /**
     * {@inheritdoc}
     */
    public function execute_command(Command_Interface $command)
    {
        if (!$this->client->is_connected()) {
            $this->get_resource();
        }
        try {
            $name = $command->get_id();
            // When using compression or a serializer, we'll need a dedicated
            // handler for `Predis\Command\RawCommand` calls, currently both
            // parameters are unsupported until a future Relay release
            return in_array($name, $this->atypical_commands) ? $this->client->{$name}(...$command->get_arguments()) : $this->client->raw_command($name, ...$command->get_arguments());
        } catch (Relay_Exception $ex) {
            $exception = $this->on_command_error($ex, $command);
            if ($exception instanceof Error_Response_Interface) {
                return $exception;
            }
            throw $exception;
        }
    }
    /**
     * {@inheritdoc}
     */
    public function on_command_error(Relay_Exception $exception, Command_Interface $command)
    {
        $code = $exception->get_code();
        $message = $exception->get_message();
        if (strpos($message, 'RELAY_ERR_IO') !== false) {
            return new Connection_Exception($this, $message, $code, $exception);
        }
        if (strpos($message, 'RELAY_ERR_REDIS') !== false) {
            return new Server_Exception($message, $code, $exception);
        }
        if (strpos($message, 'RELAY_ERR_WRONGTYPE') !== false && strpos($message, "Got reply-type 'status'") !== false) {
            $message = 'Operation against a key holding the wrong kind of value';
        }
        return new Client_Exception($message, $code, $exception);
    }
    /**
     * Applies the configured serializer and compression to given value.
     *
     * @param  mixed  $value
     */
    public function pack($value): string
    {
        return $this->client->_pack($value);
    }
    /**
     * Deserializes and decompresses to given value.
     *
     * @param  mixed  $value
     * @return string
     */
    public function unpack($value)
    {
        return $this->client->_unpack($value);
    }
    /**
     * {@inheritdoc}
     */
    public function write_request(Command_Interface $command)
    {
        throw new Not_Supported_Exception('The "relay" extension does not support writing requests.');
    }
    /**
     * {@inheritdoc}
     */
    public function read_response(Command_Interface $command)
    {
        throw new Not_Supported_Exception('The "relay" extension does not support reading responses.');
    }
    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
        $this->disconnect();
    }
    /**
     * {@inheritdoc}
     */
    protected function create_resource()
    {
        switch ($this->parameters->scheme) {
            case 'tcp':
            case 'redis':
                return $this->initialize_tcp_connection($this->parameters);
            case 'unix':
                return $this->initialize_unix_connection($this->parameters);
            default:
                throw new InvalidArgumentException("Invalid scheme: '{$this->parameters->scheme}'.");
        }
    }
    /**
     * Initializes a TCP connection via client.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return Relay
     */
    protected function initialize_tcp_connection(Parameters_Interface $parameters)
    {
        if (!filter_var($parameters->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $address = "tcp://{$parameters->host}:{$parameters->port}";
        } else {
            $address = "tcp://[{$parameters->host}]:{$parameters->port}";
        }
        $flags = STREAM_CLIENT_CONNECT;
        if (isset($parameters->async_connect) && $parameters->async_connect) {
            $flags |= STREAM_CLIENT_ASYNC_CONNECT;
        }
        if (isset($parameters->persistent)) {
            if (false !== $persistent = filter_var($parameters->persistent, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
                $flags |= STREAM_CLIENT_PERSISTENT;
                if ($persistent === null) {
                    $address = "{$address}/{$parameters->persistent}";
                }
            }
        }
        return $this->connect_with_configuration($parameters, $address, $flags);
    }
    /**
     * Initializes a UNIX connection via client.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return Relay
     */
    protected function initialize_unix_connection(Parameters_Interface $parameters)
    {
        if (!isset($parameters->path)) {
            throw new InvalidArgumentException('Missing UNIX domain socket path.');
        }
        $flags = STREAM_CLIENT_CONNECT;
        if (isset($parameters->persistent)) {
            if (false !== $persistent = filter_var($parameters->persistent, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
                $flags |= STREAM_CLIENT_PERSISTENT;
                if ($persistent === null) {
                    throw new InvalidArgumentException('Persistent connection IDs are not supported when using UNIX domain sockets.');
                }
            }
        }
        return $this->connect_with_configuration($parameters, "unix://{$parameters->path}", $flags);
    }
    /**
     * {@inheritdoc}
     */
    public function connect(): void
    {
        if (parent::connect() && $this->init_commands) {
            foreach ($this->init_commands as $command) {
                $response = $this->execute_command($command);
                if ($response instanceof Error_Response_Interface && $command->get_id() === 'CLIENT') {
                    // Do nothing on CLIENT SETINFO command failure
                } elseif ($response instanceof Error_Response_Interface) {
                    $this->on_connection_error("`{$command->get_id()}` failed: {$response->get_message()}", 0);
                }
            }
        }
    }
    /**
     * {@inheritdoc}
     */
    public function read()
    {
        throw new Not_Supported_Exception('The "relay" extension does not support reading responses.');
    }
    /**
     * {@inheritdoc}
     */
    protected function assert_parameters(Parameters_Interface $parameters): Parameters_Interface
    {
        if (!in_array($parameters->scheme, ['tcp', 'tls', 'unix', 'redis', 'rediss'])) {
            throw new InvalidArgumentException("Invalid scheme: '{$parameters->scheme}'.");
        }
        if (!in_array($parameters->serializer, [null, 'php', 'igbinary', 'msgpack', 'json'])) {
            throw new InvalidArgumentException("Invalid serializer: '{$parameters->serializer}'.");
        }
        if (!in_array($parameters->compression, [null, 'lzf', 'lz4', 'zstd'])) {
            throw new InvalidArgumentException("Invalid compression algorithm: '{$parameters->compression}'.");
        }
        return $parameters;
    }
    /**
     * {@inheritDoc}
     */
    public function write(string $buffer): void
    {
        throw new Not_Supported_Exception('The "relay" extension does not support writing operations.');
    }
}