<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Food;
use App\Model\FoodCollection;

/**
 * Storage abstraction for Food entities.
 */
interface FoodRepositoryInterface
{
    /** Persist a Food and return the stored entity (ID assigned if new). */
    public function save(Food $food): Food;

    /** Delete an item by ID. No-op if not found. */
    public function delete(int $id): void;

    /** All items, any type. */
    public function all(): FoodCollection;

    /** Only fruits. */
    public function allFruits(): FoodCollection;

    /** Only vegetables. */
    public function allVegetables(): FoodCollection;
}
