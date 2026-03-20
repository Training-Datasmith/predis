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
namespace Predis\Connection\Resource;

use InvalidArgumentException;
use Predis\Connection\Parameters_Interface;
use Predis\Connection\Resource\Exception\Stream_Init_Exception;
use Psr\Http\Message\Stream_Interface;
class Stream_Factory implements Stream_Factory_Interface
{
    /**
     * {@inheritDoc}
     * @throws StreamInitException
     */
    public function create_stream(Parameters_Interface $parameters): Stream_Interface
    {
        $parameters = $this->assert_parameters($parameters);
        switch ($parameters->scheme) {
            case 'tcp':
            case 'redis':
                $stream = $this->tcp_stream_initializer($parameters);
                break;
            case 'unix':
                $stream = $this->unix_stream_initializer($parameters);
                break;
            case 'tls':
            case 'rediss':
                $stream = $this->tls_stream_initializer($parameters);
                break;
            default:
                throw new InvalidArgumentException("Invalid scheme: '{$parameters->scheme}'.");
        }
        return new Stream($stream);
    }
    /**
     * Checks some parameters used to initialize the connection.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @throws InvalidArgumentException
     */
    protected function assert_parameters(Parameters_Interface $parameters): Parameters_Interface
    {
        switch ($parameters->scheme) {
            case 'tcp':
            case 'redis':
            case 'unix':
            case 'tls':
            case 'rediss':
                break;
            default:
                throw new InvalidArgumentException("Invalid scheme: '{$parameters->scheme}'.");
        }
        return $parameters;
    }
    /**
     * Initializes a TCP stream resource.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return resource
     * @throws StreamInitException
     */
    protected function tcp_stream_initializer(Parameters_Interface $parameters)
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
        return $this->create_stream_socket($parameters, $address, $flags);
    }
    /**
     * Initializes a UNIX stream resource.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return resource
     * @throws StreamInitException
     */
    protected function unix_stream_initializer(Parameters_Interface $parameters)
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
        return $this->create_stream_socket($parameters, "unix://{$parameters->path}", $flags);
    }
    /**
     * Initializes a SSL-encrypted TCP stream resource.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return resource
     * @throws StreamInitException
     */
    protected function tls_stream_initializer(Parameters_Interface $parameters)
    {
        $resource = $this->tcp_stream_initializer($parameters);
        $metadata = stream_get_meta_data($resource);
        // Detect if crypto mode is already enabled for this stream (PHP >= 7.0.0).
        if (isset($metadata['crypto'])) {
            return $resource;
        }
        if (isset($parameters->ssl) && is_array($parameters->ssl)) {
            $options = $parameters->ssl;
        } else {
            $options = [];
        }
        if (!isset($options['crypto_type'])) {
            $options['crypto_type'] = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        }
        $context_options = function_exists('stream_context_set_options') ? stream_context_set_options($resource, ['ssl' => $options]) : stream_context_set_option($resource, ['ssl' => $options]);
        if (!$context_options) {
            $this->on_initialization_error($resource, $parameters, 'Error while setting SSL context options');
        }
        if (!stream_socket_enable_crypto($resource, true, $options['crypto_type'])) {
            $this->on_initialization_error($resource, $parameters, 'Error while switching to encrypted communication');
        }
        return $resource;
    }
    /**
     * Creates a connected stream socket resource.
     *
     * @param ParametersInterface $parameters Connection parameters.
     * @param string              $address    Address for stream_socket_client().
     * @param int                 $flags      Flags for stream_socket_client().
     *
     * @return resource
     * @throws StreamInitException
     */
    protected function create_stream_socket(Parameters_Interface $parameters, $address, $flags)
    {
        $timeout = isset($parameters->timeout) ? (float) $parameters->timeout : 5.0;
        $context = stream_context_create(['socket' => ['tcp_nodelay' => (bool) $parameters->tcp_nodelay]]);
        if (isset($parameters->persistent) && $parameters->persistent && (isset($parameters->conn_uid) && $parameters->conn_uid)) {
            $conn_uid = '/' . $parameters->conn_uid;
        } else {
            $conn_uid = '';
        }
        // Needs to create multiple persistent connections to the same resource
        $address = $address . $conn_uid;
        if (!$resource = @stream_socket_client($address, $errno, $errstr, $timeout, $flags, $context)) {
            $this->on_initialization_error($resource, $parameters, trim($errstr), $errno);
        }
        if (isset($parameters->read_write_timeout)) {
            $rwtimeout = (float) $parameters->read_write_timeout;
            $rwtimeout = $rwtimeout > 0 ? $rwtimeout : -1;
            $timeout_seconds = floor($rwtimeout);
            $timeout_u_seconds = ($rwtimeout - $timeout_seconds) * 1000000;
            stream_set_timeout($resource, $timeout_seconds, $timeout_u_seconds);
        }
        return $resource;
    }
    /**
     * Helper method to handle connection errors.
     *
     * @param  string              $message Error message.
     * @param  int                 $code    Error code.
     * @throws StreamInitException
     */
    protected function on_initialization_error($stream, Parameters_Interface $parameters, string $message, int $code = 0): void
    {
        if (is_resource($stream)) {
            fclose($stream);
        }
        throw new Stream_Init_Exception("{$message} [{$parameters}]", $code);
    }
}