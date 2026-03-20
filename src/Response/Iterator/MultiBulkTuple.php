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

use InvalidArgumentException;
use Outer_Iterator;
use Return_Type_Will_Change;
use UnexpectedValueException;
/**
 * Outer iterator consuming streamable multibulk responses by yielding tuples of
 * keys and values.
 *
 * This wrapper is useful for responses to commands such as `HGETALL` that can
 * be iterator as $key => $value pairs.
 */
class Multi_Bulk_Tuple extends Multi_Bulk implements Outer_Iterator
{
    private $iterator;
    /**
     * @param MultiBulk $iterator Inner multibulk response iterator.
     */
    public function __construct(Multi_Bulk $iterator)
    {
        $this->check_preconditions($iterator);
        $this->size = count($iterator) / 2;
        $this->iterator = $iterator;
        $this->position = $iterator->get_position();
        $this->current = $this->size > 0 ? $this->get_value() : null;
    }
    /**
     * Checks for valid preconditions.
     *
     * @param MultiBulk $iterator Inner multibulk response iterator.
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    protected function check_preconditions(Multi_Bulk $iterator)
    {
        if ($iterator->get_position() !== 0) {
            throw new InvalidArgumentException('Cannot initialize a tuple iterator using an already initiated iterator.');
        }
        if (($size = count($iterator)) % 2 !== 0) {
            throw new UnexpectedValueException('Invalid response size for a tuple iterator.');
        }
    }
    /**
     * @return MultiBulk
     */
    #[Return_Type_Will_Change]
    public function get_inner_iterator()
    {
        return $this->iterator;
    }
    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
        $this->iterator->drop(true);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_value(): array
    {
        $k = $this->iterator->current();
        $this->iterator->next();
        $v = $this->iterator->current();
        $this->iterator->next();
        return [$k, $v];
    }
}