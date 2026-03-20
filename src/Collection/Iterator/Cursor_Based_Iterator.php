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

use Iterator;
use Predis\Client_Interface;
use Predis\Not_Supported_Exception;
use Return_Type_Will_Change;
/**
 * Provides the base implementation for a fully-rewindable PHP iterator that can
 * incrementally iterate over cursor-based collections stored on Redis using the
 * commands in the `SCAN` family.
 *
 * Given their incremental nature with multiple fetches, these kind of iterators
 * offer limited guarantees about the returned elements because the collection
 * can change several times during the iteration process.
 *
 * @see http://redis.io/commands/scan
 */
abstract class Cursor_Based_Iterator implements Iterator
{
    protected $client;
    protected $match;
    protected $count;
    protected $valid;
    protected $fetchmore;
    protected $elements;
    protected $cursor;
    protected $position;
    protected $current;
    /**
     * @param ClientInterface $client Client connected to Redis.
     * @param string          $match  Pattern to match during the server-side iteration.
     * @param int             $count  Hint used by Redis to compute the number of results per iteration.
     */
    public function __construct(Client_Interface $client, $match = null, $count = null)
    {
        $this->client = $client;
        $this->match = $match;
        $this->count = $count;
        $this->reset();
    }
    /**
     * Ensures that the client supports the specified Redis command required to
     * fetch elements from the server to perform the iteration.
     *
     * @param ClientInterface $client    Client connected to Redis.
     * @param string          $commandID Command ID.
     *
     * @throws NotSupportedException
     */
    protected function required_command(Client_Interface $client, $command_id)
    {
        if (!$client->get_command_factory()->supports($command_id)) {
            throw new Not_Supported_Exception("'{$command_id}' is not supported by the current command factory.");
        }
    }
    /**
     * Resets the inner state of the iterator.
     */
    protected function reset()
    {
        $this->valid = true;
        $this->fetchmore = true;
        $this->elements = [];
        $this->cursor = 0;
        $this->position = -1;
        $this->current = null;
    }
    /**
     * Returns an array of options for the `SCAN` command.
     *
     * @return array
     */
    protected function get_scan_options()
    {
        $options = [];
        if (strlen(strval($this->match)) > 0) {
            $options['MATCH'] = $this->match;
        }
        if ($this->count > 0) {
            $options['COUNT'] = $this->count;
        }
        return $options;
    }
    /**
     * Fetches a new set of elements from the remote collection, effectively
     * advancing the iteration process.
     *
     * @return array
     */
    abstract protected function execute_command();
    /**
     * Populates the local buffer of elements fetched from the server during
     * the iteration.
     */
    protected function fetch()
    {
        [$cursor, $elements] = $this->execute_command();
        if (!$cursor) {
            $this->fetchmore = false;
        }
        $this->cursor = $cursor;
        $this->elements = $elements;
    }
    /**
     * Extracts next values for key() and current().
     */
    protected function extract_next()
    {
        ++$this->position;
        $this->current = array_shift($this->elements);
    }
    #[Return_Type_Will_Change]
    public function rewind(): void
    {
        $this->reset();
        $this->next();
    }
    /**
     * @return mixed
     */
    #[Return_Type_Will_Change]
    public function current()
    {
        return $this->current;
    }
    /**
     * @return int|null
     */
    #[Return_Type_Will_Change]
    public function key()
    {
        return $this->position;
    }
    #[Return_Type_Will_Change]
    public function next(): void
    {
        tryFetch:
        if (!$this->elements && $this->fetchmore) {
            $this->fetch();
        }
        if ($this->elements) {
            $this->extract_next();
        } elseif ($this->cursor) {
            goto tryFetch;
        } else {
            $this->valid = false;
        }
    }
    /**
     * @return bool
     */
    #[Return_Type_Will_Change]
    public function valid()
    {
        return $this->valid;
    }
}