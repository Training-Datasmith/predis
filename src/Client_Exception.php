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

/**
 * Thrown for client-side errors that occur before a command reaches the server.
 *
 * Examples of client-side errors:
 * - Invalid connection parameters (bad URI, unsupported scheme)
 * - Attempting to execute a command against an unsupported connection type
 * - Invalid selector passed to {@see Client::get_client_by()}
 * - Callable connection factory returning a non-ConnectionInterface value
 *
 * Distinct from {@see \Predis\Response\Server_Exception}, which represents
 * errors returned by Redis itself (RESP error responses).
 *
 * @since 0.8
 */
class Client_Exception extends Predis_Exception
{
}