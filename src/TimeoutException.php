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

use Predis\Connection\Node_Connection_Interface;
use Throwable;
class Timeout_Exception extends Communication_Exception
{
    public function __construct(Node_Connection_Interface $connection, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($connection, 'Operation has timed out', $code, $previous);
    }
}