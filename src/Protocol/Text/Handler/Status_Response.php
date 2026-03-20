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
namespace Predis\Protocol\Text\Handler;

use Predis\Connection\Composite_Connection_Interface;
use Predis\Response\Status;
/**
 * Handler for the status response type in the standard Redis wire protocol. It
 * translates certain classes of status response to PHP objects or just returns
 * the payload as a string.
 *
 * @see http://redis.io/topics/protocol
 */
class Status_Response implements Response_Handler_Interface
{
    /**
     * {@inheritdoc}
     */
    public function handle(Composite_Connection_Interface $connection, $payload)
    {
        return Status::get($payload);
    }
}