<?php

namespace App\Tests\App\Model;

use App\Model\Food;
use App\Model\FoodType;
use App\Model\FruitCollection;
use App\Model\VegetableCollection;
use PHPUnit\Framework\TestCase;

class FruitAndVegetableCollectionTest extends TestCase
{
    public function testFruitCollectionAcceptsOnlyFruits(): void
    {
        $fruitCollection = new FruitCollection();
        $apple = new Food(null, 'Apple', FoodType::FRUIT, 100);
        $carrot = new Food(null, 'Carrot', FoodType::VEGETABLE, 100);

        $fruitCollection->add($apple);
        self::assertCount(1, $fruitCollection->search(null));

        $this->expectException(\InvalidArgumentException::class);
        $fruitCollection->add($carrot);
    }

    public function testVegetableCollectionAcceptsOnlyVegetables(): void
    {
        $vegetableCollection = new VegetableCollection();
        $carrot = new Food(null, 'Carrot', FoodType::VEGETABLE, 100);
        $apple = new Food(null, 'Apple', FoodType::FRUIT, 100);

        $vegetableCollection->add($carrot);
        self::assertCount(1, $vegetableCollection->search(null));

        $this->expectException(\InvalidArgumentException::class);
        $vegetableCollection->add($apple);
    }
}
