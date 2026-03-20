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
use Predis\Protocol\Parser\Parser_Strategy_Resolver;
use Predis\Protocol\Parser\Strategy\Parser_Strategy_Interface;
use Predis\Protocol\Protocol_Exception;
use Predis\Timeout_Exception;
/**
 * Base class with the common logic used by connection classes to communicate
 * with Redis.
 */
abstract class Abstract_Connection implements Node_Connection_Interface
{
    /**
     * @var ParserStrategyInterface
     */
    protected $parser_strategy;
    /**
     * @var int|null
     */
    protected $client_id;
    protected $resource;
    private $cached_id;
    protected $parameters;
    /**
     * @var RawCommand[]
     */
    protected $init_commands = [];
    /**
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     */
    public function __construct(Parameters_Interface $parameters)
    {
        $this->parameters = $parameters;
        $this->set_parser_strategy();
    }
    /**
     * Disconnects from the server and destroys the underlying resource when
     * PHP's garbage collector kicks in.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
    /**
     * {@inheritdoc}
     */
    public function is_connected()
    {
        return isset($this->resource);
    }
    /**
     * {@inheritdoc}
     */
    public function has_data_to_read(): bool
    {
        return true;
    }
    /**
     * Creates a stream resource to communicate with Redis.
     *
     * @return mixed
     * @throws StreamInitException
     */
    abstract protected function create_resource();
    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if (!$this->is_connected()) {
            $this->resource = $this->create_resource();
            return true;
        }
        return false;
    }
    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        unset($this->resource);
    }
    /**
     * {@inheritdoc}
     */
    public function add_connect_command(Command_Interface $command): void
    {
        $this->init_commands[] = $command;
    }
    /**
     * {@inheritdoc}
     */
    public function get_init_commands(): array
    {
        return $this->init_commands;
    }
    /**
     * {@inheritdoc}
     */
    public function execute_command(Command_Interface $command)
    {
        $this->write_request($command);
        return $this->read_response($command);
    }
    /**
     * {@inheritdoc}
     */
    public function read_response(Command_Interface $command)
    {
        return $this->read();
    }
    /**
     * Helper method to handle connection errors.
     *
     * @param  string                 $message Error message.
     * @param  int                    $code    Error code.
     * @throws CommunicationException
     */
    protected function on_connection_error($message, $code = 0): void
    {
        Communication_Exception::handle(new Connection_Exception($this, "{$message} [{$this->get_parameters()}]", $code));
    }
    /**
     * Helper method to handle timeout errors.
     *
     * @throws CommunicationException
     */
    protected function on_timeout_error(int $code = 0): void
    {
        Communication_Exception::handle(new Timeout_Exception($this, $code));
    }
    /**
     * Helper method to handle protocol errors.
     *
     * @param  string                 $message Error message.
     * @throws CommunicationException
     */
    protected function on_protocol_error($message)
    {
        Communication_Exception::handle(new Protocol_Exception($this, "{$message} [{$this->get_parameters()}]"));
    }
    /**
     * {@inheritdoc}
     */
    public function get_resource()
    {
        if (isset($this->resource)) {
            return $this->resource;
        }
        $this->connect();
        return $this->resource;
    }
    /**
     * {@inheritdoc}
     */
    public function get_parameters()
    {
        return $this->parameters;
    }
    /**
     * Gets an identifier for the connection.
     *
     * @return string
     */
    protected function get_identifier()
    {
        if ($this->parameters->scheme === 'unix') {
            return $this->parameters->path;
        }
        return "{$this->parameters->host}:{$this->parameters->port}";
    }
    /**
     * {@inheritDoc}
     */
    public function get_client_id(): ?int
    {
        return $this->client_id;
    }
    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        if (!isset($this->cached_id)) {
            $this->cached_id = $this->get_identifier();
        }
        return $this->cached_id;
    }
    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return ['parameters', 'initCommands'];
    }
    /**
     * Set parser strategy for given connection.
     */
    protected function set_parser_strategy(): void
    {
        $strategy_resolver = new Parser_Strategy_Resolver();
        $this->parser_strategy = $strategy_resolver->resolve((int) $this->parameters->protocol);
    }
}