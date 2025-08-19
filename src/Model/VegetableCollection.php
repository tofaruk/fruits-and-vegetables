<?php
declare(strict_types=1);

namespace App\Model;

/**
 * Collection that only accepts vegetables.
 */
class VegetableCollection extends FoodCollection
{
    public function add(Food $food): void
    {
        if ($food->getType() !== FoodType::VEGETABLE) {
            throw new \InvalidArgumentException('Only vegetables are allowed in VegetableCollection.');
        }
        parent::add($food);
    }
}
