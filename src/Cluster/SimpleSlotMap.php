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
namespace Predis\Cluster;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use OutOfBoundsException;
use Predis\Connection\Node_Connection_Interface;
use Return_Type_Will_Change;
use Traversable;
/**
 * Slot map for redis-cluster.
 */
class Simple_Slot_Map implements ArrayAccess, IteratorAggregate, Countable
{
    private $slots = [];
    /**
     * Checks if the given slot is valid.
     *
     * @param int $slot Slot index.
     */
    public static function is_valid($slot): bool
    {
        return $slot >= 0x0 && $slot <= 0x3fff;
    }
    /**
     * Checks if the given slot range is valid.
     *
     * @param int $first Initial slot of the range.
     * @param int $last  Last slot of the range.
     */
    public static function is_valid_range($first, $last): bool
    {
        return $first >= 0x0 && $first <= 0x3fff && $last >= 0x0 && $last <= 0x3fff && $first <= $last;
    }
    /**
     * Resets the slot map.
     */
    public function reset(): void
    {
        $this->slots = [];
    }
    /**
     * Checks if the slot map is empty.
     */
    public function is_empty(): bool
    {
        return empty($this->slots);
    }
    /**
     * Returns the current slot map as a dictionary of $slot => $node.
     *
     * The order of the slots in the dictionary is not guaranteed.
     *
     * @return array
     */
    public function to_array()
    {
        return $this->slots;
    }
    /**
     * Returns the list of unique nodes in the slot map.
     */
    public function get_nodes(): array
    {
        return array_keys(array_flip($this->slots));
    }
    /**
     * Assigns the specified slot range to a node.
     *
     * @param int                            $first      Initial slot of the range.
     * @param int                            $last       Last slot of the range.
     * @param NodeConnectionInterface|string $connection ID or connection instance.
     *
     * @throws OutOfBoundsException
     */
    public function set_slots($first, $last, $connection): void
    {
        if (!static::is_valid_range($first, $last)) {
            throw new OutOfBoundsException("Invalid slot range {$first}-{$last} for `{$connection}`");
        }
        $this->slots += array_fill($first, $last - $first + 1, (string) $connection);
    }
    /**
     * Returns the specified slot range.
     *
     * @param int $first Initial slot of the range.
     * @param int $last  Last slot of the range.
     */
    public function get_slots($first, $last): array
    {
        if (!static::is_valid_range($first, $last)) {
            throw new OutOfBoundsException("Invalid slot range {$first}-{$last}");
        }
        return array_intersect_key($this->slots, array_fill($first, $last - $first + 1, null));
    }
    /**
     * Checks if the specified slot is assigned.
     *
     * @param int $slot Slot index.
     *
     * @return bool
     */
    #[Return_Type_Will_Change]
    public function offsetExists($slot)
    {
        return isset($this->slots[$slot]);
    }
    /**
     * Returns the node assigned to the specified slot.
     *
     * @param int $slot Slot index.
     *
     * @return string|null
     */
    #[Return_Type_Will_Change]
    public function offsetGet($slot)
    {
        return $this->slots[$slot] ?? null;
    }
    /**
     * Assigns the specified slot to a node.
     *
     * @param int                            $slot       Slot index.
     * @param NodeConnectionInterface|string $connection ID or connection instance.
     */
    #[Return_Type_Will_Change]
    public function offsetSet($slot, $connection): void
    {
        if (!static::is_valid($slot)) {
            throw new OutOfBoundsException("Invalid slot {$slot} for `{$connection}`");
        }
        $this->slots[(int) $slot] = (string) $connection;
    }
    /**
     * Returns the node assigned to the specified slot.
     *
     * @param int $slot Slot index.
     */
    #[Return_Type_Will_Change]
    public function offsetUnset($slot): void
    {
        unset($this->slots[$slot]);
    }
    /**
     * Returns the current number of assigned slots.
     *
     * @return int
     */
    #[Return_Type_Will_Change]
    public function count()
    {
        return count($this->slots);
    }
    /**
     * Returns an iterator over the slot map.
     *
     * @return Traversable<int, string>
     */
    #[Return_Type_Will_Change]
    public function getIterator()
    {
        return new ArrayIterator($this->slots);
    }
}