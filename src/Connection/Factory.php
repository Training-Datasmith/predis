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
use Predis\Client;
use Predis\Command\Raw_Command;
use ReflectionClass;
use UnexpectedValueException;
/**
 * Standard connection factory for creating connections to Redis nodes.
 */
class Factory implements Factory_Interface
{
    private $defaults = [];
    /**
     * @var string|null
     */
    private $upstream_driver;
    protected $schemes = ['tcp' => \Predis\Connection\Stream_Connection::class, 'unix' => \Predis\Connection\Stream_Connection::class, 'tls' => \Predis\Connection\Stream_Connection::class, 'redis' => \Predis\Connection\Stream_Connection::class, 'rediss' => \Predis\Connection\Stream_Connection::class];
    /**
     * Checks if the provided argument represents a valid connection class
     * implementing Predis\Connection\NodeConnectionInterface. Optionally,
     * callable objects are used for lazy initialization of connection objects.
     *
     * @param mixed $initializer FQN of a connection class or a callable for lazy initialization.
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function check_initializer($initializer)
    {
        if (is_callable($initializer)) {
            return $initializer;
        }
        $class = new ReflectionClass($initializer);
        if (!$class->is_subclass_of(\Predis\Connection\Node_Connection_Interface::class)) {
            throw new InvalidArgumentException('A connection initializer must be a valid connection class or a callable object.');
        }
        return $initializer;
    }
    /**
     * {@inheritdoc}
     */
    public function define($scheme, $initializer): void
    {
        $this->schemes[$scheme] = $this->check_initializer($initializer);
    }
    /**
     * {@inheritdoc}
     */
    public function undefine($scheme): void
    {
        unset($this->schemes[$scheme]);
    }
    /**
     * {@inheritdoc}
     */
    public function create($parameters)
    {
        if (!$parameters instanceof Parameters_Interface) {
            $parameters = $this->create_parameters($parameters);
        }
        $scheme = $parameters->scheme;
        if (!isset($this->schemes[$scheme])) {
            throw new InvalidArgumentException("Unknown connection scheme: '{$scheme}'.");
        }
        $initializer = $this->schemes[$scheme];
        if (is_callable($initializer)) {
            $connection = call_user_func($initializer, $parameters, $this);
        } else {
            $connection = new $initializer($parameters);
            $this->prepare_connection($connection);
        }
        if (!$connection instanceof Node_Connection_Interface) {
            throw new UnexpectedValueException('Objects returned by connection initializers must implement ' . "'Predis\\Connection\\NodeConnectionInterface'.");
        }
        return $connection;
    }
    /**
     * Assigns a default set of parameters applied to new connections.
     *
     * The set of parameters passed to create a new connection have precedence
     * over the default values set for the connection factory.
     *
     * @param array $parameters Set of connection parameters.
     */
    public function set_default_parameters(array $parameters): void
    {
        $this->defaults = $parameters;
    }
    /**
     * Returns the default set of parameters applied to new connections.
     *
     * @return array
     */
    public function get_default_parameters()
    {
        return $this->defaults;
    }
    /**
     * Sets upstream driver information for CLIENT SETINFO.
     *
     * @param string $driver Upstream driver string (e.g., 'laravel_v11.0.0' or 'laravel_v11.0.0;my-app_v1.0.0').
     */
    public function set_upstream_driver(string $driver): void
    {
        $this->upstream_driver = $driver;
    }
    /**
     * Returns the configured upstream driver.
     */
    public function get_upstream_driver(): ?string
    {
        return $this->upstream_driver;
    }
    /**
     * Creates a connection parameters instance from the supplied argument.
     *
     * @param mixed $parameters Original connection parameters.
     *
     * @return ParametersInterface
     */
    protected function create_parameters($parameters): \Predis\Connection\Parameters
    {
        if (is_string($parameters)) {
            $parameters = Parameters::parse($parameters);
        } else {
            $parameters = $parameters ?: [];
        }
        if ($this->defaults) {
            $parameters += $this->defaults;
        }
        return new Parameters($parameters);
    }
    /**
     * Prepares a connection instance after its initialization.
     *
     * @param NodeConnectionInterface $connection Connection instance.
     */
    protected function prepare_connection(Node_Connection_Interface $connection)
    {
        $parameters = $connection->get_parameters();
        if (!empty($parameters->password)) {
            $cmd_auth_args = [$parameters->protocol, 'AUTH'];
            if (empty($parameters->username)) {
                $parameters->username = 'default';
            }
            array_push($cmd_auth_args, $parameters->username, $parameters->password);
            array_push($cmd_auth_args, 'SETNAME', 'predis');
            $connection->add_connect_command(new Raw_Command('HELLO', $cmd_auth_args));
        } else {
            $connection->add_connect_command(new Raw_Command('HELLO', [$parameters->protocol ?? 2, 'SETNAME', 'predis']));
        }
        $connection->add_connect_command(new Raw_Command('CLIENT', ['SETINFO', 'LIB-NAME', $this->build_library_name()]));
        $connection->add_connect_command(new Raw_Command('CLIENT', ['SETINFO', 'LIB-VER', Client::VERSION]));
        if (isset($parameters->database) && strlen($parameters->database)) {
            $connection->add_connect_command(new Raw_Command('SELECT', [$parameters->database]));
        }
    }
    /**
     * Builds the library name string for CLIENT SETINFO.
     */
    protected function build_library_name(): string
    {
        return $this->upstream_driver ? 'predis(' . $this->upstream_driver . ')' : 'predis';
    }
}