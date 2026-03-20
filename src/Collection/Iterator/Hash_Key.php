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
namespace Predis\Collection\Iterator;

use Predis\Client_Interface;
/**
 * Abstracts the iteration of fields and values of an hash by leveraging the
 * HSCAN command (Redis >= 2.8) wrapped in a fully-rewindable PHP iterator.
 *
 * @see http://redis.io/commands/scan
 */
class Hash_Key extends Cursor_Based_Iterator
{
    protected $key;
    /**
     * {@inheritdoc}
     */
    public function __construct(Client_Interface $client, $key, $match = null, $count = null)
    {
        $this->required_command($client, 'HSCAN');
        parent::__construct($client, $match, $count);
        $this->key = $key;
    }
    /**
     * {@inheritdoc}
     */
    protected function execute_command()
    {
        return $this->client->hscan($this->key, $this->cursor, $this->get_scan_options());
    }
    /**
     * {@inheritdoc}
     */
    protected function extract_next()
    {
        $this->position = key($this->elements);
        $this->current = current($this->elements);
        unset($this->elements[$this->position]);
    }
}