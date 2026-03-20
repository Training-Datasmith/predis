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
namespace Predis\Transaction\Response;

use Predis\Response\Response_Interface;
/**
 * Wrapper for the responses that associated with commands executed bypassing transaction logic.
 */
class Bypass_Transaction_Response implements Response_Interface
{
    /**
     * @var mixed
     */
    private $response;
    public function __construct($response)
    {
        $this->response = $response;
    }
    /**
     * @return mixed
     */
    public function get_response()
    {
        return $this->response;
    }
}