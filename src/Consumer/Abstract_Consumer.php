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

use Predis\Client_Interface;
use Return_Type_Will_Change;
abstract class Abstract_Consumer implements Consumer_Interface
{
    /**
     * @var ClientInterface
     */
    protected $client;
    /**
     * @var bool
     */
    protected $is_valid = true;
    /**
     * @var int
     */
    protected $position = 0;
    public function __construct(Client_Interface $client)
    {
        $this->client = $client;
    }
    /**
     * {@inheritDoc}
     */
    public function stop(bool $drop = false): bool
    {
        $this->is_valid = false;
        if ($drop) {
            $this->client->disconnect();
            return true;
        }
        return true;
    }
    public function get_client(): Client_Interface
    {
        return $this->client;
    }
    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->get_value();
    }
    /**
     * Returns last message from server.
     *
     * @return mixed
     */
    #[Return_Type_Will_Change]
    abstract protected function get_value();
    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return $this->is_valid;
    }
    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        if ($this->valid()) {
            ++$this->position;
        }
    }
    /**
     * {@inheritDoc}
     */
    #[Return_Type_Will_Change]
    public function key()
    {
        return $this->position;
    }
    /**
     * {@inheritDoc}
     */
    #[Return_Type_Will_Change]
    public function rewind(): void
    {
        // NOOP
    }
}