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
namespace Predis\Response;

use Predis\Predis_Exception;
/**
 * Exception class that identifies server-side Redis errors.
 */
class Server_Exception extends Predis_Exception implements Error_Interface
{
    /**
     * Gets the type of the error returned by Redis.
     */
    public function get_error_type(): string
    {
        [$error_type] = explode(' ', $this->get_message(), 2);
        return $error_type;
    }
    /**
     * Converts the exception to an instance of Predis\Response\Error.
     *
     * @return Error
     */
    public function to_error_response(): \Predis\Response\Error
    {
        return new Error($this->get_message());
    }
}