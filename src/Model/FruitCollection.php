<?php
declare(strict_types=1);

namespace App\Model;

/**
 * Collection that only accepts fruits.
 */
class FruitCollection extends FoodCollection
{
    public function add(Food $food): void
    {
        if ($food->getType() !== FoodType::FRUIT) {
            throw new \InvalidArgumentException('Only fruits are allowed in FruitCollection.');
        }
        parent::add($food);
    }
}
