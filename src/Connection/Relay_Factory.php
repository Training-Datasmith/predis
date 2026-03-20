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
use Predis\Command\Raw_Command;
use Predis\Not_Supported_Exception;
use Predis\Retry\Strategy\Equal_Backoff;
use Predis\Retry\Strategy\Exponential_Backoff;
use Relay\Relay;
class Relay_Factory extends Factory
{
    /**
     * @var string[]
     */
    protected $schemes = ['tcp' => Relay_Connection::class, 'tls' => Relay_Connection::class, 'unix' => Relay_Connection::class, 'redis' => Relay_Connection::class, 'rediss' => Relay_Connection::class];
    /**
     * {@inheritDoc}
     */
    public function define($scheme, $initializer)
    {
        throw new Not_Supported_Exception('Does not allow to override existing initializer.');
    }
    /**
     * {@inheritDoc}
     */
    public function undefine($scheme)
    {
        throw new Not_Supported_Exception('Does not allow to override existing initializer.');
    }
    /**
     * {@inheritDoc}
     */
    public function create($parameters): Node_Connection_Interface
    {
        $this->assert_extensions();
        if (!$parameters instanceof Parameters_Interface) {
            $parameters = $this->create_parameters($parameters);
        }
        $scheme = $parameters->scheme;
        if (!isset($this->schemes[$scheme])) {
            throw new InvalidArgumentException("Unknown connection scheme: '{$scheme}'.");
        }
        $initializer = $this->schemes[$scheme];
        $client = $this->create_client($parameters);
        $connection = new $initializer($parameters, $client);
        $this->prepare_connection($connection);
        return $connection;
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
     * Creates a new instance of the client.
     */
    private function create_client(Parameters_Interface $parameters): \Relay\Relay
    {
        $client = new Relay();
        // throw when errors occur and return `null` for non-existent keys
        $client->set_option(Relay::OPT_PHPREDIS_COMPATIBILITY, false);
        // use reply literals
        $client->set_option(Relay::OPT_REPLY_LITERAL, true);
        // whether to use in-memory caching
        $client->set_option(Relay::OPT_USE_CACHE, $parameters->cache ?? true);
        // set data serializer
        $client->set_option(Relay::OPT_SERIALIZER, constant(sprintf('%s::SERIALIZER_%s', Relay::class, strtoupper($parameters->serializer ?? 'none'))));
        // set data compression algorithm
        $client->set_option(Relay::OPT_COMPRESSION, constant(sprintf('%s::COMPRESSION_%s', Relay::class, strtoupper($parameters->compression ?? 'none'))));
        if ($parameters->is_disabled_retry()) {
            $client->set_option(Relay::OPT_MAX_RETRIES, 0);
        } else {
            $client->set_option(Relay::OPT_MAX_RETRIES, $parameters->retry->get_retries());
            $retry_strategy = $parameters->retry->get_strategy();
            if ($retry_strategy instanceof Exponential_Backoff) {
                $algorithm = Relay::BACKOFF_ALGORITHM_FULL_JITTER;
                $base = $retry_strategy->get_base();
                $cap = $retry_strategy->get_cap();
            } else {
                $algorithm = Relay::BACKOFF_ALGORITHM_DEFAULT;
                if ($retry_strategy instanceof Equal_Backoff) {
                    $base = $cap = $retry_strategy->compute(0);
                } else {
                    $base = $retry_strategy::DEFAULT_BASE;
                    $cap = $retry_strategy::DEFAULT_CAP;
                }
            }
            $client->set_option(Relay::OPT_BACKOFF_ALGORITHM, $algorithm);
            $client->set_option(Relay::OPT_BACKOFF_BASE, $base / 1000);
            $client->set_option(Relay::OPT_BACKOFF_CAP, $cap / 1000);
        }
        return $client;
    }
    /**
     * {@inheritdoc}
     */
    protected function prepare_connection(Node_Connection_Interface $connection)
    {
        $parameters = $connection->get_parameters();
        if (isset($parameters->password) && strlen($parameters->password)) {
            $cmd_auth_args = isset($parameters->username) && strlen($parameters->username) ? [$parameters->username, $parameters->password] : [$parameters->password];
            $connection->add_connect_command(new Raw_Command('AUTH', $cmd_auth_args));
        }
        if (isset($parameters->database) && strlen($parameters->database)) {
            $connection->add_connect_command(new Raw_Command('SELECT', [$parameters->database]));
        }
    }
}