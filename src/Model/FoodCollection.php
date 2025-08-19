<?php
declare(strict_types=1);

namespace App\Model;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * Collection of Food items. Iterable and countable.
 *
 * @implements IteratorAggregate<int,Food>
 */
class FoodCollection implements IteratorAggregate, Countable
{
    /** @var array<int,Food> */
    private array $items = [];

    /** @param Food ...$items */
    public function __construct(Food ...$items)
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /** Add or replace an item by its ID (or object hash when ID is null). */
    public function add(Food $food): void
    {
        $key = $food->getId() ?? spl_object_id($food);
        $this->items[$key] = $food;
    }

    /** Case-insensitive substring search on name. */
    public function search(?string $query): self
    {
        if ($query === null || $query === '') {
            return new self(...$this->items);
        }
        $needle = mb_strtolower($query);
        return new self(...array_values(array_filter(
            $this->items,
            static fn(Food $f): bool => str_contains(mb_strtolower($f->getName()), $needle)
        )));
    }

    /** Export as plain array. @return list<Food> */
    public function toArray(): array
    {
        return array_values($this->items);
    }

    /** @inheritDoc */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->toArray());
    }

    /** @inheritDoc */
    public function count(): int
    {
        return \count($this->items);
    }
}
