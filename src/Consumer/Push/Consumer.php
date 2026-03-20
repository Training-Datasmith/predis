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
namespace Predis\Consumer\Push;

use Predis\Client_Interface;
use Predis\Connection\Node_Connection_Interface;
use Predis\Consumer\Abstract_Consumer;
class Consumer extends Abstract_Consumer
{
    /**
     * @param callable|null   $preLoopCallback Callback that should be called on client before enter a loop.
     */
    public function __construct(Client_Interface $client, ?callable $pre_loop_callback = null)
    {
        parent::__construct($client);
        if (null !== $pre_loop_callback) {
            $pre_loop_callback($this->client);
        }
    }
    public function current(): ?Push_Response_Interface
    {
        return parent::current();
    }
    /**
     * Reads line from connection and returns push response or null on any other type.
     */
    protected function get_value(): ?Push_Response_Interface
    {
        /** @var NodeConnectionInterface $connection */
        $connection = $this->client->get_connection();
        $response = $connection->read();
        return $response instanceof Push_Response ? $response : null;
    }
}