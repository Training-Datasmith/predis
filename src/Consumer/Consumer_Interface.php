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
namespace Predis\Consumer;

use Iterator;
use Predis\Client_Interface;
use Return_Type_Will_Change;
interface Consumer_Interface extends Iterator
{
    public function __construct(Client_Interface $client);
    /**
     * Stops consumer loop, with optional client disconnection.
     */
    public function stop(bool $drop = false): bool;
    /**
     * Returns consumer client instance.
     */
    public function get_client(): Client_Interface;
    /**
     * Returns last payload produced by server.
     *
     * @return mixed
     */
    #[Return_Type_Will_Change]
    public function current();
    /**
     * Keeps loop until consumer is in valid state.
     *
     * @return void
     */
    #[Return_Type_Will_Change]
    public function next();
    /**
     * Checks if consumer is in the valid state to continue.
     *
     * @return bool
     */
    #[Return_Type_Will_Change]
    public function valid();
}