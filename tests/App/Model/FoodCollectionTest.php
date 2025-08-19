<?php
declare(strict_types=1);

namespace App\Tests\App\Model;

use App\Model\Food;
use App\Model\FoodCollection;
use App\Model\FoodType;
use PHPUnit\Framework\TestCase;

class FoodCollectionTest extends TestCase
{
    public function testAddSearchFilterAndRemove(): void
    {
        $foodA = new Food(1, 'Apple', FoodType::FRUIT, 120);
        $foodB = new Food(2, 'Broccoli', FoodType::VEGETABLE, 300);
        $foodC = new Food(3, 'Banana', FoodType::FRUIT, 90);

        $foodCollection = new FoodCollection($foodA, $foodB, $foodC);
        self::assertCount(3, $foodCollection);

        $search = $foodCollection->search('ap');
        self::assertSame([1], array_map(fn(Food $food) => $food->getId(), $search->toArray()));
    }
}
