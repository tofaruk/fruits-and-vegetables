<?php

namespace App\Tests\App\Model;

use App\Model\FoodType;
use PHPUnit\Framework\TestCase;

class FoodTypeTest extends TestCase
{
    public function testFromString(): void
    {
        self::assertSame(FoodType::FRUIT, FoodType::fromString('fruit'));
        self::assertSame(FoodType::VEGETABLE, FoodType::fromString('vegetable'));
        self::assertSame(FoodType::FRUIT, FoodType::fromString('FRUIT'));
        self::assertSame(FoodType::VEGETABLE, FoodType::fromString('VEGETABLE'));
    }

    public function testFromStringInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FoodType::fromString('meat');
    }
}
