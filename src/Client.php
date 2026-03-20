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
namespace Predis;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use Predis\Command\Command_Interface;
use Predis\Command\Container\Container_Factory;
use Predis\Command\Container\Container_Interface;
use Predis\Command\Raw_Command;
use Predis\Command\Script_Command;
use Predis\Configuration\Options;
use Predis\Configuration\Options_Interface;
use Predis\Connection\Aggregate_Connection_Interface;
use Predis\Connection\Connection_Interface;
use Predis\Connection\Parameters;
use Predis\Connection\Parameters_Interface;
use Predis\Connection\Relay_Connection;
use Predis\Consumer\Pub_Sub\Consumer as PubSubConsumer;
use Predis\Consumer\Pub_Sub\Relay_Consumer as RelayPubSubConsumer;
use Predis\Consumer\Push\Consumer as PushConsumer;
use Predis\Monitor\Consumer as MonitorConsumer;
use Predis\Pipeline\Atomic;
use Predis\Pipeline\Fire_And_Forget;
use Predis\Pipeline\Pipeline;
use Predis\Pipeline\Relay_Atomic;
use Predis\Pipeline\Relay_Pipeline;
use Predis\Response\Error_Interface as ErrorResponseInterface;
use Predis\Response\Response_Interface;
use Predis\Response\Server_Exception;
use Predis\Transaction\Multi_Exec as MultiExecTransaction;
use Return_Type_Will_Change;
use RuntimeException;
use Throwable;
use Traversable;
/**
 * Client class used for connecting and executing commands on Redis.
 *
 * This is the main high-level abstraction of Predis upon which various other
 * abstractions are built. Internally it aggregates various other classes each
 * one with its own responsibility and scope.
 *
 * @template-implements \IteratorAggregate<string, static>
 */
class Client implements Client_Interface, IteratorAggregate
{
    public const VERSION = '3.4.3-dev';
    /** @var OptionsInterface */
    private $options;
    /** @var ConnectionInterface */
    private $connection;
    /** @var Command\FactoryInterface */
    private $commands;
    /**
     * Creates a new Predis client instance.
     *
     * The $parameters argument accepts several forms:
     * - `null` — connects to `tcp://127.0.0.1:6379` with defaults
     * - `string` — Redis URI, e.g. `tcp://127.0.0.1:6379?database=1`
     * - `array` (dictionary) — connection parameters as key-value pairs
     * - `array` (indexed) — multiple servers for cluster/replication
     * - `Parameters_Interface` — pre-built parameters object
     * - `Connection_Interface` — pre-built connection (used as-is)
     * - `callable` — factory receiving `OptionsInterface`, returning `ConnectionInterface`
     *
     * The $options argument accepts:
     * - `null` — use default options
     * - `array` — options map (see {@see Options} for supported keys)
     * - `Options_Interface` — pre-built options object
     *
     * @param null|string|array<string|int, mixed>|Parameters_Interface|Connection_Interface|callable $parameters
     *        Connection parameters for one or more servers.
     * @param null|array<string, mixed>|Options_Interface $options
     *        Client configuration options.
     *
     * @throws \InvalidArgumentException When $options is not a recognised type.
     * @since  0.1
     */
    public function __construct($parameters = null, $options = null)
    {
        $this->options = static::create_options($options ?? new Options());
        $this->connection = static::create_connection($this->options, $parameters ?? new Parameters());
        $this->commands = $this->options->commands;
    }
    /**
     * Creates a new set of client options for the client.
     *
     * @param array|OptionsInterface $options Set of client options
     *
     * @return OptionsInterface
     * @throws InvalidArgumentException
     */
    protected static function create_options($options)
    {
        if (is_array($options)) {
            return new Options($options);
        }
        if ($options instanceof Options_Interface) {
            return $options;
        }
        throw new InvalidArgumentException('Invalid type for client options');
    }
    /**
     * Creates single or aggregate connections from supplied arguments.
     *
     * This method accepts the following types to create a connection instance:
     *
     *  - Array (dictionary: single connection, indexed: aggregate connections)
     *  - String (URI for a single connection)
     *  - Callable (connection initializer callback)
     *  - Instance of Predis\Connection\ParametersInterface (used as-is)
     *  - Instance of Predis\Connection\ConnectionInterface (returned as-is)
     *
     * When a callable is passed, it receives the original set of client options
     * and must return an instance of Predis\Connection\ConnectionInterface.
     *
     * Connections are created using the connection factory (in case of single
     * connections) or a specialized aggregate connection initializer (in case
     * of cluster and replication) retrieved from the supplied client options.
     *
     * @param OptionsInterface $options    Client options container
     * @param mixed            $parameters Connection parameters
     *
     * @return ConnectionInterface
     * @throws InvalidArgumentException
     */
    protected static function create_connection(Options_Interface $options, $parameters)
    {
        if ($parameters instanceof Connection_Interface) {
            return $parameters;
        }
        if ($parameters instanceof Parameters_Interface || is_string($parameters)) {
            return $options->connections->create($parameters);
        }
        if (is_array($parameters)) {
            if (!isset($parameters[0])) {
                return $options->connections->create($parameters);
            }
            if ($options->defined('cluster') && $initializer = $options->cluster) {
                return $initializer($parameters, true);
            }
            if ($options->defined('replication') && $initializer = $options->replication) {
                return $initializer($parameters, true);
            }
            if ($options->defined('aggregate') && $initializer = $options->aggregate) {
                return $initializer($parameters, false);
            }
            throw new InvalidArgumentException('Array of connection parameters requires `cluster`, `replication` or `aggregate` client option');
        }
        if (is_callable($parameters)) {
            $connection = call_user_func($parameters, $options);
            if (!$connection instanceof Connection_Interface) {
                throw new InvalidArgumentException('Callable parameters must return a valid connection');
            }
            return $connection;
        }
        throw new InvalidArgumentException('Invalid type for connection parameters');
    }
    /**
     * {@inheritdoc}
     */
    public function get_command_factory()
    {
        return $this->commands;
    }
    /**
     * {@inheritdoc}
     */
    public function get_options()
    {
        return $this->options;
    }
    /**
     * Creates a new client using a specific underlying connection.
     *
     * This method allows to create a new client instance by picking a specific
     * connection out of an aggregate one, with the same options of the original
     * client instance.
     *
     * The specified selector defines which logic to use to look for a suitable
     * connection by the specified value. Supported selectors are:
     *
     *   - `id`
     *   - `key`
     *   - `slot`
     *   - `command`
     *   - `alias`
     *   - `role`
     *
     * Internally the client relies on duck-typing and follows this convention:
     *
     *   $selector string => getConnectionBy$selector($value) method
     *
     * This means that support for specific selectors may vary depending on the
     * actual logic implemented by connection classes and there is no interface
     * binding a connection class to implement any of these.
     *
     * @param string $selector Type of selector.
     * @param mixed  $value    Value to be used by the selector.
     *
     * @return ClientInterface
     */
    public function get_client_by($selector, $value): self
    {
        $selector = strtolower($selector);
        if (!in_array($selector, ['id', 'key', 'slot', 'role', 'alias', 'command'])) {
            throw new InvalidArgumentException("Invalid selector type: `{$selector}`");
        }
        if (!method_exists($this->connection, $method = "getConnectionBy{$selector}")) {
            $class = get_class($this->connection);
            throw new InvalidArgumentException("Selecting connection by {$selector} is not supported by {$class}");
        }
        if (!$connection = $this->connection->{$method}($value)) {
            throw new InvalidArgumentException("Cannot find a connection by {$selector} matching `{$value}`");
        }
        return new static($connection, $this->get_options());
    }
    /**
     * Opens the underlying connection and connects to the server.
     */
    public function connect(): void
    {
        $this->connection->connect();
    }
    /**
     * Closes the underlying connection and disconnects from the server.
     */
    public function disconnect(): void
    {
        $this->connection->disconnect();
    }
    /**
     * Closes the underlying connection to the server.
     *
     * This method does NOT send the Redis `QUIT` command. It simply closes the
     * socket. It is identical to calling {@see disconnect()} directly.
     *
     * @deprecated since 2.0 — Use {@see disconnect()} instead for clarity.
     *             The name `quit` implies sending a QUIT command to Redis, which
     *             this method does not do.
     */
    public function quit(): void
    {
        trigger_error('Client::quit() is deprecated since Predis 2.0; use Client::disconnect() instead.', E_USER_DEPRECATED);
        $this->disconnect();
    }
    /**
     * Returns the current state of the underlying connection.
     *
     * @return bool
     */
    public function is_connected()
    {
        return $this->connection->is_connected();
    }
    /**
     * {@inheritdoc}
     */
    public function get_connection()
    {
        return $this->connection;
    }
    /**
     * Applies the configured serializer and compression to given value.
     *
     * @param  mixed  $value
     * @return string
     */
    public function pack($value)
    {
        return $this->connection instanceof Relay_Connection ? $this->connection->pack($value) : $value;
    }
    /**
     * Deserializes and decompresses to given value.
     *
     * @param  mixed  $value
     * @return string
     */
    public function unpack($value)
    {
        return $this->connection instanceof Relay_Connection ? $this->connection->unpack($value) : $value;
    }
    /**
     * Sends a raw command to Redis, bypassing all client-side processing.
     *
     * This method skips:
     * - Argument filtering and validation
     * - Response parsing (returns the raw RESP value)
     * - Key prefixing
     * - Exception throwing on Redis error responses
     *
     * Useful for sending commands not yet implemented by Predis, or for
     * debugging by inspecting the literal server response.
     *
     * @param  array<int, string|int|float> $arguments Command name followed by arguments,
     *                                                  e.g. `['SET', 'key', 'value']`.
     * @param  bool                         $error     Passed by reference; set to true when
     *                                                  Redis returned an error (RESP `-ERR …`).
     *
     * @return mixed The raw response value from Redis (string, int, array, or null).
     * @since  1.0
     * @see    execute_command() For the full-featured command execution path.
     */
    public function execute_raw(array $arguments, &$error = null)
    {
        $error = false;
        $command_id = array_shift($arguments);
        $response = $this->connection->execute_command(new Raw_Command($command_id, $arguments));
        if ($response instanceof Response_Interface) {
            if ($response instanceof Error_Response_Interface) {
                $error = true;
            }
            return (string) $response;
        }
        return $response;
    }
    /**
     * {@inheritdoc}
     */
    public function __call($command_id, $arguments)
    {
        return $this->execute_command($this->create_command($command_id, $arguments));
    }
    /**
     * {@inheritdoc}
     */
    public function create_command($command_id, $arguments = []): \Predis\Command\Command_Interface
    {
        return $this->commands->create($command_id, $arguments);
    }
    /**
     * @return ContainerInterface
     */
    public function __get(string $name)
    {
        return Container_Factory::create($this, $name);
    }
    /**
     * @param  mixed  $value
     * @return mixed
     */
    public function __set(string $name, $value)
    {
        throw new RuntimeException('Not allowed');
    }
    /**
     * @return mixed
     */
    public function __isset(string $name)
    {
        throw new RuntimeException('Not allowed');
    }
    /**
     * {@inheritdoc}
     * @throws Throwable
     */
    public function execute_command(Command_Interface $command)
    {
        $parameters = $this->connection->get_parameters();
        if ($this->connection instanceof Aggregate_Connection_Interface || $this->connection instanceof Relay_Connection) {
            $response = $this->connection->execute_command($command);
        } else {
            $response = $parameters->retry->call_with_retry(function () use ($command) {
                return $this->connection->execute_command($command);
            }, function (): void {
                $this->connection->disconnect();
            });
        }
        if ($response instanceof Response_Interface) {
            if ($response instanceof Error_Response_Interface) {
                return $this->on_error_response($command, $response);
            }
            return $response;
        }
        if ($parameters->protocol === 2) {
            return $command->parse_response($response);
        }
        return $command->parse_resp3response($response);
    }
    /**
     * Handles -ERR responses returned by Redis.
     *
     * @param CommandInterface       $command  Redis command that generated the error.
     * @param ErrorResponseInterface $response Instance of the error response.
     *
     * @return mixed
     * @throws ServerException
     */
    protected function on_error_response(Command_Interface $command, Error_Response_Interface $response)
    {
        if ($command instanceof Script_Command && $response->get_error_type() === 'NOSCRIPT') {
            $response = $this->execute_command($command->get_eval_command());
            if (!$response instanceof Response_Interface) {
                return $command->parse_response($response);
            }
            return $response;
        }
        if ($this->options->exceptions) {
            throw new Server_Exception($response->get_message());
        }
        return $response;
    }
    /**
     * Executes the specified initializer method on `$this` by adjusting the
     * actual invocation depending on the arity (0, 1 or 2 arguments). This is
     * simply an utility method to create Redis contexts instances since they
     * follow a common initialization path.
     *
     * @param string $initializer Method name.
     * @param array  $argv        Arguments for the method.
     *
     * @return mixed
     */
    private function shared_context_factory(string $initializer, $argv = null)
    {
        switch (count($argv)) {
            case 0:
                return $this->{$initializer}();
            case 1:
                return is_array($argv[0]) ? $this->{$initializer}($argv[0]) : $this->{$initializer}(null, $argv[0]);
            case 2:
                [$arg0, $arg1] = $argv;
                return $this->{$initializer}($arg0, $arg1);
            default:
                return $this->{$initializer}($this, $argv);
        }
    }
    /**
     * Creates a new pipeline context and returns it, or returns the results of
     * a pipeline executed inside the optionally provided callable object.
     *
     * @param mixed ...$arguments Array of options, a callable for execution, or both.
     *
     * @return Pipeline|array
     */
    public function pipeline(...$arguments)
    {
        return $this->shared_context_factory('createPipeline', func_get_args());
    }
    /**
     * Actual pipeline context initializer method.
     *
     * @param array|null $options  Options for the context.
     * @param mixed      $callable Optional callable used to execute the context.
     *
     * @return Pipeline|array
     */
    protected function create_pipeline(?array $options = null, $callable = null)
    {
        if (isset($options['atomic']) && $options['atomic']) {
            $class = Atomic::class;
        } elseif (isset($options['fire-and-forget']) && $options['fire-and-forget']) {
            $class = Fire_And_Forget::class;
        } else {
            $class = Pipeline::class;
        }
        if ($this->connection instanceof Relay_Connection) {
            if (isset($options['atomic']) && $options['atomic']) {
                $class = Relay_Atomic::class;
            } elseif (isset($options['fire-and-forget']) && $options['fire-and-forget']) {
                throw new Not_Supported_Exception('The "relay" extension does not support fire-and-forget pipelines.');
            } else {
                $class = Relay_Pipeline::class;
            }
        }
        /*
         * @var ClientContextInterface
         */
        $pipeline = new $class($this);
        if (isset($callable)) {
            return $pipeline->execute($callable);
        }
        return $pipeline;
    }
    /**
     * Creates a new transaction context and returns it, or returns the results
     * of a transaction executed inside the optionally provided callable object.
     *
     * @param mixed ...$arguments Array of options, a callable for execution, or both.
     *
     * @return MultiExecTransaction|array
     */
    public function transaction(...$arguments)
    {
        return $this->shared_context_factory('createTransaction', func_get_args());
    }
    /**
     * Actual transaction context initializer method.
     *
     * @param array|null $options  Options for the context.
     * @param mixed      $callable Optional callable used to execute the context.
     *
     * @return MultiExecTransaction|array
     */
    protected function create_transaction(?array $options = null, $callable = null)
    {
        $transaction = new Multi_Exec_Transaction($this, $options);
        if (isset($callable)) {
            return $transaction->execute($callable);
        }
        return $transaction;
    }
    /**
     * Creates a new publish/subscribe context and returns it, or starts its loop
     * inside the optionally provided callable object.
     *
     * @param mixed ...$arguments Array of options, a callable for execution, or both.
     *
     * @return PubSubConsumer|null
     */
    public function pub_sub_loop(...$arguments)
    {
        return $this->shared_context_factory('createPubSub', func_get_args());
    }
    /**
     * Creates new push notifications consumer.
     *
     * @param  callable|null $preLoopCallback Callback that should be called on client before enter a loop.
     */
    public function push(?callable $pre_loop_callback = null): Push_Consumer
    {
        return new Push_Consumer($this, $pre_loop_callback);
    }
    /**
     * Actual publish/subscribe context initializer method.
     *
     * @param array|null $options  Options for the context.
     * @param mixed      $callable Optional callable used to execute the context.
     *
     * @return PubSubConsumer|null
     */
    protected function create_pub_sub(?array $options = null, $callable = null)
    {
        if ($this->connection instanceof Relay_Connection) {
            $pubsub = new Relay_Pub_Sub_Consumer($this, $options);
        } else {
            $pubsub = new Pub_Sub_Consumer($this, $options);
        }
        if (!isset($callable)) {
            return $pubsub;
        }
        foreach ($pubsub as $message) {
            if (call_user_func($callable, $pubsub, $message) === false) {
                $pubsub->stop();
            }
        }
        return null;
    }
    /**
     * Creates a new monitor consumer and returns it.
     */
    public function monitor(): \Predis\Monitor\Consumer
    {
        return new Monitor_Consumer($this);
    }
    /**
     * @return Traversable<string, static>
     */
    #[Return_Type_Will_Change]
    public function getIterator()
    {
        $clients = [];
        $connection = $this->get_connection();
        if (!$connection instanceof Traversable) {
            return new ArrayIterator([(string) $connection => new static($connection, $this->get_options())]);
        }
        foreach ($connection as $node) {
            $clients[(string) $node] = new static($node, $this->get_options());
        }
        return new ArrayIterator($clients);
    }
}