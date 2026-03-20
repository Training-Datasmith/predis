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
namespace Predis\Response\Iterator;

use Countable;
use Iterator;
use Predis\Response\Response_Interface;
use Return_Type_Will_Change;
/**
 * Iterator that abstracts the access to multibulk responses allowing them to be
 * consumed in a streamable fashion without keeping the whole payload in memory.
 *
 * This iterator does not support rewinding which means that the iteration, once
 * consumed, cannot be restarted.
 *
 * Always make sure that the whole iteration is consumed (or dropped) to prevent
 * protocol desynchronization issues.
 */
abstract class Multi_Bulk_Iterator implements Iterator, Countable, Response_Interface
{
    protected $current;
    protected $position;
    protected $size;
    #[Return_Type_Will_Change]
    public function rewind(): void
    {
        // NOOP
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
        if (++$this->position < $this->size) {
            $this->current = $this->get_value();
        }
    }
    /**
     * @return bool
     */
    #[Return_Type_Will_Change]
    public function valid()
    {
        return $this->position < $this->size;
    }
    /**
     * Returns the number of items comprising the whole multibulk response.
     *
     * This method should be used instead of iterator_count() to get the size of
     * the current multibulk response since the former consumes the iteration to
     * count the number of elements, but our iterators do not support rewinding.
     *
     * @return int
     */
    #[Return_Type_Will_Change]
    public function count()
    {
        return $this->size;
    }
    /**
     * Returns the current position of the iterator.
     *
     * @return int
     */
    public function get_position()
    {
        return $this->position;
    }
    /**
     * {@inheritdoc}
     */
    abstract protected function get_value();
}