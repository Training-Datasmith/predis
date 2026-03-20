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

use Predis\Connection\Node_Connection_Interface;
use Predis\Transaction\Multi_Exec_State;
class Node_Connection_Strategy extends Non_Cluster_Connection_Strategy
{
    /**
     * @var NodeConnectionInterface
     */
    protected $connection;
    public function __construct(Node_Connection_Interface $connection, Multi_Exec_State $state)
    {
        parent::__construct($connection, $state);
    }
}