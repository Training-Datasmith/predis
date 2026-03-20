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
namespace Predis\Connection\Resource;

use Predis\Connection\Parameters_Interface;
use Psr\Http\Message\Stream_Interface;
interface Stream_Factory_Interface
{
    /**
     * Creates stream from given parameters.
     */
    public function create_stream(Parameters_Interface $parameters): Stream_Interface;
}