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
namespace Predis\Command\Container;

use Predis\Client_Interface;
abstract class Abstract_Container implements Container_Interface
{
    /**
     * @var ClientInterface
     */
    protected $client;
    public function __construct(Client_Interface $client)
    {
        $this->client = $client;
    }
    /**
     * {@inheritDoc}
     */
    public function __call(string $subcommand_id, array $arguments)
    {
        array_unshift($arguments, strtoupper($subcommand_id));
        return $this->client->execute_command($this->client->create_command($this->get_container_command_id(), $arguments));
    }
    abstract public function get_container_command_id(): string;
}