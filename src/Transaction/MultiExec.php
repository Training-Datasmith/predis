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
namespace Predis\Transaction;

use Exception;
use InvalidArgumentException;
use Predis\Client_Context_Interface;
use Predis\Client_Exception;
use Predis\Client_Interface;
use Predis\Command\Command_Interface;
use Predis\Communication_Exception;
use Predis\Not_Supported_Exception;
use Predis\Protocol\Protocol_Exception;
use Predis\Response\Error;
use Predis\Response\Error_Interface as ErrorResponseInterface;
use Predis\Response\Server_Exception;
use Predis\Response\Status as StatusResponse;
use Predis\Transaction\Response\Bypass_Transaction_Response;
use Predis\Transaction\Strategy\Connection_Strategy_Resolver;
use Predis\Transaction\Strategy\Strategy_Interface;
use Predis\Transaction\Strategy\Strategy_Resolver_Interface;
use Relay\Exception as RelayException;
use Relay\Relay;
use SplQueue;
/**
 * Client-side abstraction of a Redis transaction based on MULTI / EXEC.
 *
 * {@inheritdoc}
 */
class Multi_Exec implements Client_Context_Interface
{
    private $state;
    protected $client;
    protected $commands;
    protected $exceptions = true;
    protected $attempts = 0;
    protected $watch_keys = [];
    protected $mode_cas = false;
    /**
     * @var StrategyInterface
     */
    protected $connection_strategy;
    /**
     * @param  ClientInterface       $client  Client instance used by the transaction.
     * @param  array|null            $options Initialization options.
     * @throws NotSupportedException
     */
    public function __construct(Client_Interface $client, ?array $options = null, ?Strategy_Resolver_Interface $strategy_resolver = null)
    {
        $this->assert_client($client);
        $this->client = $client;
        $this->state = new Multi_Exec_State();
        if (null === $strategy_resolver) {
            $strategy_resolver = new Connection_Strategy_Resolver();
        }
        $this->connection_strategy = $strategy_resolver->resolve($client->get_connection(), $this->state);
        $this->configure($client, $options ?: []);
        $this->reset();
    }
    /**
     * Checks if the passed client instance satisfies the required conditions
     * needed to initialize the transaction object.
     *
     * @param ClientInterface $client Client instance used by the transaction object.
     *
     * @throws NotSupportedException
     */
    private function assert_client(Client_Interface $client): void
    {
        if (!$client->get_command_factory()->supports('MULTI', 'EXEC', 'DISCARD')) {
            throw new Not_Supported_Exception('MULTI, EXEC and DISCARD are not supported by the current command factory.');
        }
    }
    /**
     * Configures the transaction using the provided options.
     *
     * @param ClientInterface $client  Underlying client instance.
     * @param array           $options Array of options for the transaction.
     **/
    protected function configure(Client_Interface $client, array $options)
    {
        if (isset($options['exceptions'])) {
            $this->exceptions = (bool) $options['exceptions'];
        } else {
            $this->exceptions = $client->get_options()->exceptions;
        }
        if (isset($options['cas'])) {
            $this->mode_cas = (bool) $options['cas'];
        }
        if (isset($options['watch']) && $keys = $options['watch']) {
            $this->watch_keys = $keys;
        }
        if (isset($options['retry'])) {
            $this->attempts = (int) $options['retry'];
        }
    }
    /**
     * Resets the state of the transaction.
     */
    protected function reset()
    {
        $this->state->reset();
        $this->commands = new SplQueue();
    }
    /**
     * Initializes the transaction context.
     */
    protected function initialize()
    {
        if ($this->state->is_initialized()) {
            return;
        }
        if ($this->mode_cas) {
            $this->state->flag(Multi_Exec_State::CAS);
        }
        if ($this->watch_keys) {
            $this->watch($this->watch_keys);
        }
        $cas = $this->state->is_cas();
        $discarded = $this->state->is_discarded();
        if (!$cas || $cas && $discarded) {
            $this->connection_strategy->initialize_transaction();
            if ($discarded) {
                $this->state->unflag(Multi_Exec_State::CAS);
            }
        }
        $this->state->unflag(Multi_Exec_State::DISCARDED);
        $this->state->flag(Multi_Exec_State::INITIALIZED);
    }
    /**
     * Dynamically invokes a Redis command with the specified arguments.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->execute_command($this->client->create_command($method, $arguments));
    }
    /**
     * Executes the specified Redis command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return $this|mixed
     * @throws AbortedMultiExecException
     * @throws CommunicationException
     */
    public function execute_command(Command_Interface $command)
    {
        $this->initialize();
        $response = $this->connection_strategy->execute_command($command);
        if ($response instanceof Bypass_Transaction_Response) {
            return $response->get_response();
        }
        if ($response instanceof Status_Response && $response == 'QUEUED') {
            $this->commands->enqueue($command);
        } elseif ($response instanceof Relay) {
            $this->commands->enqueue($command);
        } elseif ($response instanceof Error_Response_Interface) {
            throw new Aborted_Multi_Exec_Exception($this, $response->get_message());
        } else {
            $this->on_protocol_error('The server did not return a +QUEUED status response.');
        }
        return $this;
    }
    /**
     * Executes WATCH against one or more keys.
     *
     * @param string|array $keys One or more keys.
     *
     * @return mixed
     * @throws NotSupportedException
     * @throws ClientException
     */
    public function watch($keys)
    {
        if (!$this->client->get_command_factory()->supports('WATCH')) {
            throw new Not_Supported_Exception('WATCH is not supported by the current command factory.');
        }
        if ($this->state->is_watch_allowed()) {
            throw new Client_Exception('Sending WATCH after MULTI is not allowed.');
        }
        $response = $this->connection_strategy->watch(is_array($keys) ? $keys : [$keys]);
        $this->state->flag(Multi_Exec_State::WATCH);
        return $response;
    }
    /**
     * Finalizes the transaction by executing MULTI on the server.
     */
    public function multi(): self
    {
        if ($this->state->check(Multi_Exec_State::INITIALIZED | Multi_Exec_State::CAS)) {
            $this->state->unflag(Multi_Exec_State::CAS);
            $this->connection_strategy->multi();
        } else {
            $this->initialize();
        }
        return $this;
    }
    /**
     * Executes UNWATCH.
     *
     * @throws NotSupportedException
     */
    public function unwatch(): self
    {
        if (!$this->client->get_command_factory()->supports('UNWATCH')) {
            throw new Not_Supported_Exception('UNWATCH is not supported by the current command factory.');
        }
        $this->state->unflag(Multi_Exec_State::WATCH);
        $this->__call('UNWATCH', []);
        return $this;
    }
    /**
     * Resets the transaction by UNWATCH-ing the keys that are being WATCHed and
     * DISCARD-ing pending commands that have been already sent to the server.
     */
    public function discard(): self
    {
        if ($this->state->is_initialized()) {
            if ($this->state->is_cas()) {
                $this->connection_strategy->unwatch();
            } else {
                $this->connection_strategy->discard();
            }
            $this->reset();
            $this->state->flag(Multi_Exec_State::DISCARDED);
        }
        return $this;
    }
    /**
     * Executes the whole transaction.
     *
     * @return mixed
     */
    public function exec()
    {
        return $this->execute();
    }
    /**
     * Checks the state of the transaction before execution.
     *
     * @param mixed $callable Callback for execution.
     *
     * @throws InvalidArgumentException
     * @throws ClientException
     */
    private function check_before_execution($callable): void
    {
        if ($this->state->is_executing()) {
            throw new Client_Exception('Cannot invoke "execute" or "exec" inside an active transaction context.');
        }
        if ($callable) {
            if (!is_callable($callable)) {
                throw new InvalidArgumentException('The argument must be a callable object.');
            }
            if (!$this->commands->is_empty()) {
                $this->discard();
                throw new Client_Exception('Cannot execute a transaction block after using fluent interface.');
            }
        } elseif ($this->attempts) {
            $this->discard();
            throw new Client_Exception('Automatic retries are supported only when a callable block is provided.');
        }
    }
    /**
     * Handles the actual execution of the whole transaction.
     *
     * @param mixed $callable Optional callback for execution.
     *
     * @return array
     * @throws CommunicationException
     * @throws AbortedMultiExecException
     * @throws ServerException
     */
    public function execute($callable = null)
    {
        $this->check_before_execution($callable);
        $exec_response = null;
        $attempts = $this->attempts;
        do {
            if ($callable) {
                $this->execute_transaction_block($callable);
            }
            if ($this->commands->is_empty()) {
                if ($this->state->is_watching()) {
                    $this->discard();
                }
                return;
            }
            $exec_response = $this->connection_strategy->execute_transaction();
            // The additional `false` check is needed for Relay,
            // let's hope it won't break anything
            if ($exec_response === null || $exec_response === false) {
                if ($attempts === 0) {
                    throw new Aborted_Multi_Exec_Exception($this, 'The current transaction has been aborted by the server.');
                }
                $this->reset();
                continue;
            }
            break;
        } while ($attempts-- > 0);
        $response = [];
        $commands = $this->commands;
        $size = count($exec_response);
        $protocol_version = $this->client->get_connection()->get_parameters()->protocol;
        if ($size !== count($commands)) {
            $this->on_protocol_error('EXEC returned an unexpected number of response items.');
        }
        for ($i = 0; $i < $size; ++$i) {
            $cmd_response = $exec_response[$i];
            if ($this->exceptions && $cmd_response instanceof Error_Response_Interface) {
                throw new Server_Exception($cmd_response->get_message());
            }
            if ($cmd_response instanceof Relay_Exception) {
                if ($this->exceptions) {
                    throw new Server_Exception($cmd_response->get_message(), $cmd_response->get_code(), $cmd_response);
                }
                $commands->dequeue();
                $response[$i] = new Error($cmd_response->get_message());
                continue;
            }
            if ($protocol_version === 2) {
                $response[$i] = $commands->dequeue()->parse_response($cmd_response);
            } else {
                $response[$i] = $commands->dequeue()->parse_resp3response($cmd_response);
            }
        }
        return $response;
    }
    /**
     * Passes the current transaction object to a callable block for execution.
     *
     * @param mixed $callable Callback.
     *
     * @throws CommunicationException
     * @throws ServerException
     */
    protected function execute_transaction_block($callable)
    {
        $exception = null;
        $this->state->flag(Multi_Exec_State::INSIDEBLOCK);
        try {
            call_user_func($callable, $this);
        } catch (Communication_Exception|Server_Exception $exception) {
            // NOOP
        } catch (\Throwable $exception) {
            $this->discard();
        }
        $this->state->unflag(Multi_Exec_State::INSIDEBLOCK);
        if ($exception) {
            throw $exception;
        }
    }
    /**
     * Helper method for protocol errors encountered inside the transaction.
     *
     * @param string $message Error message.
     */
    private function on_protocol_error(string $message): void
    {
        // Since a MULTI/EXEC block cannot be initialized when using aggregate
        // connections we can safely assume that Predis\Client::getConnection()
        // will return a Predis\Connection\NodeConnectionInterface instance.
        Communication_Exception::handle(new Protocol_Exception($this->client->get_connection(), $message));
    }
}