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
 * Compact slot map for Redis Cluster.
 *
 * Stores a list of {@see Slot_Range} objects that together cover [0, 16383].
 * Adjacent ranges assigned to the same node are merged into a single range to
 * keep memory usage minimal. The map supports ArrayAccess so individual slots
 * can be read and written as `$map[$slot]`.
 *
 * @since 1.0
 */
class Slot_Map implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * Sorted, compacted list of slot ranges.
     *
     * @var list<Slot_Range>
     */
    private array $slot_ranges = [];
    /**
     * Returns true when the given integer is a valid Redis Cluster slot number.
     *
     * Valid slot numbers are in [0, {@see Slot_Range::MAX_SLOTS}] (i.e. 0–16383).
     *
     * @param  int  $slot The slot number to check.
     * @return bool True when $slot is in the valid range.
     * @since  1.0
     */
    public static function is_valid(int $slot): bool
    {
        return $slot >= 0 && $slot <= Slot_Range::MAX_SLOTS;
    }

    /**
     * Returns true when [$first, $last] is a valid Redis Cluster slot range.
     *
     * @param  int  $first First slot (inclusive).
     * @param  int  $last  Last slot (inclusive).
     * @return bool True when both endpoints are valid and $first <= $last.
     * @since  1.0
     */
    public static function is_valid_range(int $first, int $last): bool
    {
        return Slot_Range::is_valid_range($first, $last);
    }

    /**
     * Clears all slot assignments from the map.
     *
     * @since 1.0
     */
    public function reset(): void
    {
        $this->slot_ranges = [];
    }

    /**
     * Returns true when no slots are assigned.
     *
     * @return bool True when the map contains no slot ranges.
     * @since  1.0
     */
    public function is_empty(): bool
    {
        return empty($this->slot_ranges);
    }

    /**
     * Expands all slot ranges into a flat `$slot => $nodeId` dictionary.
     *
     * Note: this allocates an array with up to 16384 entries.
     *
     * @return array<int, string> Slot number → node ID string.
     * @complexity O(n) where n = total number of assigned slots (up to 16384).
     * @since 1.0
     */
    public function to_array(): array
    {
        return array_reduce($this->slot_ranges, static function (array $carry, \Predis\Cluster\Slot_Range $slot_range): array {
            return $carry + $slot_range->to_array();
        }, []);
    }

    /**
     * Returns the unique list of node connection IDs present in the slot map.
     *
     * @return list<string> Unique node ID strings.
     * @since  1.0
     */
    public function get_nodes(): array
    {
        return array_unique(array_map(static function (\Predis\Cluster\Slot_Range $slot_range): string {
            return $slot_range->get_connection();
        }, $this->slot_ranges));
    }

    /**
     * Returns the internal list of slot range objects.
     *
     * @return list<Slot_Range> Sorted slot ranges.
     * @since  1.0
     */
    public function get_slot_ranges(): array
    {
        return $this->slot_ranges;
    }

    /**
     * Assigns a contiguous range of slots to a node.
     *
     * Only unassigned (gap) slots within [$first, $last] are affected.
     * Already-assigned slots in that range retain their current assignment.
     * Adjacent ranges belonging to the same node are merged automatically.
     *
     * @param int                                      $first      First slot (inclusive).
     * @param int                                      $last       Last slot (inclusive).
     * @param Node_Connection_Interface|string         $connection The node to assign, or its string ID.
     *
     * @throws OutOfBoundsException When [$first, $last] is not a valid slot range.
     * @since  1.0
     * @see    offsetSet() To assign a single slot.
     */
    public function set_slots(int $first, int $last, $connection): void
    {
        if (!static::is_valid_range($first, $last)) {
            throw new OutOfBoundsException("Invalid slot range {$first}-{$last} for `{$connection}`");
        }
        $target_slot_range = new Slot_Range($first, $last, (string) $connection);
        // Get gaps of slot ranges list.
        $gaps = $this->get_gaps($this->slot_ranges);
        $results = $this->slot_ranges;
        foreach ($gaps as $gap) {
            if (!$gap->has_intersection_with($target_slot_range)) {
                continue;
            }
            // Get intersection of the gap and target slot range.
            $results[] = new Slot_Range(max($gap->get_start(), $target_slot_range->get_start()), min($gap->get_end(), $target_slot_range->get_end()), $target_slot_range->get_connection());
        }
        $this->sort_slot_ranges($results);
        $results = $this->compact_slot_ranges($results);
        $this->slot_ranges = $results;
    }
    /**
     * Returns the specified slot range.
     *
     * @param int $first Initial slot of the range.
     * @param int $last  Last slot of the range.
     *
     * @return array<int, string>
     */
    public function get_slots($first, $last)
    {
        if (!static::is_valid_range($first, $last)) {
            throw new OutOfBoundsException("Invalid slot range {$first}-{$last}");
        }
        $place_holder = new Null_Slot_Range($first, $last);
        $intersections = [];
        foreach ($this->slot_ranges as $slot_range) {
            if (!$place_holder->has_intersection_with($slot_range)) {
                continue;
            }
            $intersections[] = new Slot_Range(max($place_holder->get_start(), $slot_range->get_start()), min($place_holder->get_end(), $slot_range->get_end()), $slot_range->get_connection());
        }
        return array_reduce($intersections, static function (array $carry, \Predis\Cluster\Slot_Range $slot_range): array {
            return $carry + $slot_range->to_array();
        }, []);
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
        return $this->find_range_by_slot($slot) !== false;
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
        $found = $this->find_range_by_slot($slot);
        return $found ? $found->get_connection() : null;
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
        $this->offsetUnset($slot);
        $this->set_slots($slot, $slot, $connection);
    }
    /**
     * Returns the node assigned to the specified slot.
     *
     * @param int $slot Slot index.
     */
    #[Return_Type_Will_Change]
    public function offsetUnset($slot): void
    {
        if (!static::is_valid($slot)) {
            throw new OutOfBoundsException("Invalid slot {$slot}");
        }
        $results = [];
        foreach ($this->slot_ranges as $slot_range) {
            if (!$slot_range->has_slot($slot)) {
                $results[] = $slot_range;
            }
            if (static::is_valid_range($slot_range->get_start(), $slot - 1)) {
                $results[] = new Slot_Range($slot_range->get_start(), $slot - 1, $slot_range->get_connection());
            }
            if (static::is_valid_range($slot + 1, $slot_range->get_end())) {
                $results[] = new Slot_Range($slot + 1, $slot_range->get_end(), $slot_range->get_connection());
            }
        }
        $this->slot_ranges = $results;
    }
    /**
     * Returns the current number of assigned slots.
     *
     * @return int
     */
    #[Return_Type_Will_Change]
    public function count()
    {
        return array_sum(array_map(static function (\Predis\Cluster\Slot_Range $slot_range): int {
            return $slot_range->count();
        }, $this->slot_ranges));
    }
    /**
     * Returns an iterator over the slot map.
     *
     * @return Traversable<int, string>
     */
    #[Return_Type_Will_Change]
    public function getIterator()
    {
        return new ArrayIterator($this->to_array());
    }
    /**
     * Find the slot range which contains the specific slot index.
     *
     * @param int $slot Slot index.
     *
     * @return SlotRange|false The slot range object or false if not found.
     */
    protected function find_range_by_slot(int $slot)
    {
        foreach ($this->slot_ranges as $slot_range) {
            if ($slot_range->has_slot($slot)) {
                return $slot_range;
            }
        }
        return false;
    }
    /**
     * Get gaps between sorted slot ranges with NullSlotRange object.
     *
     * @param SlotRange[] $slotRanges
     *
     * @return SlotRange[]
     */
    protected function get_gaps(array $slot_ranges): array
    {
        if (empty($slot_ranges)) {
            return [new Null_Slot_Range(0, Slot_Range::MAX_SLOTS)];
        }
        $gaps = [];
        $count = count($slot_ranges);
        $i = 0;
        foreach ($slot_ranges as $key => $slot_range) {
            $start = $slot_range->get_start();
            $end = $slot_range->get_end();
            if (static::is_valid_range($i, $start - 1)) {
                $gaps[] = new Null_Slot_Range($i, $start - 1);
            }
            $i = $end + 1;
            if ($key === $count - 1) {
                if (static::is_valid_range($i, Slot_Range::MAX_SLOTS)) {
                    $gaps[] = new Null_Slot_Range($i, Slot_Range::MAX_SLOTS);
                }
            }
        }
        return $gaps;
    }
    /**
     * Sort slot ranges by start index.
     *
     * @param SlotRange[] $slotRanges
     *
     * @return void
     */
    protected function sort_slot_ranges(array &$slot_ranges)
    {
        usort($slot_ranges, static function (Slot_Range $a, Slot_Range $b): int {
            return $a->get_start() <=> $b->get_start();
        });
    }
    /**
     * Compact adjacent slot ranges with the same connection.
     *
     * @param SlotRange[] $slotRanges
     *
     * @return SlotRange[]
     */
    protected function compact_slot_ranges(array $slot_ranges): array
    {
        if (empty($slot_ranges)) {
            return [];
        }
        $compacted = [];
        $count = count($slot_ranges);
        $i = 0;
        $carry = $slot_ranges[0];
        while ($i < $count) {
            $next = $slot_ranges[$i + 1] ?? null;
            if (!is_null($next) && $carry->get_end() + 1 === $next->get_start() && $carry->get_connection() === $next->get_connection()) {
                $carry = new Slot_Range($carry->get_start(), $next->get_end(), $carry->get_connection());
            } else {
                $compacted[] = $carry;
                $carry = $next;
            }
            $i++;
        }
        return array_values($compacted);
    }
}