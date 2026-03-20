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
namespace Predis\Transaction\Strategy;

use InvalidArgumentException;
use Predis\Connection\Cluster\Cluster_Interface;
use Predis\Connection\Connection_Interface;
use Predis\Connection\Node_Connection_Interface;
use Predis\Connection\Replication\Replication_Interface;
use Predis\Transaction\Multi_Exec_State;
class Connection_Strategy_Resolver implements Strategy_Resolver_Interface
{
    /**
     * @var array{string: string}
     */
    private $strategy_mapping = [Cluster_Interface::class => Cluster_Connection_Strategy::class, Node_Connection_Interface::class => Node_Connection_Strategy::class, Replication_Interface::class => Replication_Connection_Strategy::class];
    /**
     * {@inheritDoc}
     */
    public function resolve(Connection_Interface $connection, Multi_Exec_State $state): Strategy_Interface
    {
        foreach ($this->strategy_mapping as $interface => $strategy) {
            if ($connection instanceof $interface) {
                return new $strategy($connection, $state);
            }
        }
        throw new InvalidArgumentException('Cannot resolve strategy associated with this connection type');
    }
}