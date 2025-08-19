<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Food;

/**
 * Application service for managing Food items.
 */
interface FoodServiceInterface
{
    /** Create and persist a Food from user input. */
    public function add(string $name, string $type, float|int $quantity, string $unit): Food;

    /** Delete an item by ID. */
    public function remove(int $id): void;

    /**
     * Query items by optional type and search term.
     * @return list<Food>
     */
    public function query(?string $type = null, ?string $search = null): array;

    /**
     * Present a Food as an API-friendly array in a given unit.
     * @return array{id:int|null,name:string,type:string,quantity:int|float,unit:string}
     */
    public function format(Food $food, string $unit = 'g'): array;
}
