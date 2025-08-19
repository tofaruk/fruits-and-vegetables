<?php
declare(strict_types=1);

namespace App\Tests\App\Service;

use App\Model\Food;
use App\Model\FoodCollection;
use App\Model\FoodType;
use App\Repository\FoodRepositoryInterface;
use App\Service\FoodService;
use PHPUnit\Framework\TestCase;

class FoodServiceTest extends TestCase
{
    private FoodRepositoryInterface $foodRepositoryMock;

    private FoodService $foodService;

    protected function setUp(): void
    {
        $this->foodRepositoryMock = $this->createMock(FoodRepositoryInterface::class);
        $this->foodService = new FoodService($this->foodRepositoryMock);
    }

    public function testAddConvertsKgToGramsAndSaves(): void
    {

        $this->foodRepositoryMock
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(function (Food $food) {
                self::assertNull($food->getId(), 'ID should be null before saving');
                self::assertSame('Banana', $food->getName());
                self::assertSame(FoodType::FRUIT, $food->getType());
                self::assertSame(1200, $food->getGrams(), 'Converted grams must be 1200');
                return new Food(10, $food->getName(), $food->getType(), $food->getGrams());
            });

        $savedFood = $this->foodService->add('Banana', 'fruit', 1.2, 'kg');

        self::assertSame(10, $savedFood->getId());
        self::assertSame(1200, $savedFood->getGrams());
    }

    public function testAddWithGramsPassesThrough(): void
    {
        $this->foodRepositoryMock
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(function (Food $food) {
                self::assertSame('Carrot', $food->getName());
                self::assertSame(FoodType::VEGETABLE, $food->getType());
                self::assertSame(250, $food->getGrams());
                return new Food(5, $food->getName(), $food->getType(), $food->getGrams());
            });

        $savedFood = $this->foodService->add('Carrot', 'vegetable', 250, 'g');

        self::assertSame(5, $savedFood->getId());
        self::assertSame(250, $savedFood->getGrams());
    }

    public function testRemoveForwardsToRepository(): void
    {
        $this->foodRepositoryMock
            ->expects(self::once())
            ->method('delete')
            ->with(42);

        $this->foodService->remove(42);
        $this->addToAssertionCount(1);
    }

    public function testQueryDelegatesToAllWhenTypeIsNullAndForwardsSearch(): void
    {
        $foods = [
            new Food(1, 'Apple', FoodType::FRUIT, 150),
            new Food(2, 'Apricot', FoodType::FRUIT, 50),
        ];

        $collection = new FoodCollection(...$foods);

        $this->foodRepositoryMock
            ->expects(self::once())
            ->method('all')
            ->willReturn($collection);

        $result = $this->foodService->query(null, 'ap');

        self::assertCount(2, $result);
        self::assertSame('Apple', $result[0]->getName());
        self::assertSame('Apricot', $result[1]->getName());
    }

    public function testQueryDelegatesToAllFruits(): void
    {
        $foods = [new Food(3, 'Pear', FoodType::FRUIT, 120)];

        $collection = new FoodCollection(...$foods);

        $this->foodRepositoryMock
            ->expects(self::once())
            ->method('allFruits')
            ->willReturn($collection);

        $result = $this->foodService->query('fruit', null);

        self::assertCount(1, $result);
        self::assertSame('Pear', $result[0]->getName());
        self::assertSame(FoodType::FRUIT, $result[0]->getType());
    }

    public function testQueryDelegatesToAllVegetables(): void
    {
        $foods = [new Food(4, 'Broccoli', FoodType::VEGETABLE, 300)];
        $collection = new FoodCollection(...$foods);

        $this->foodRepositoryMock
            ->expects(self::once())
            ->method('allVegetables')
            ->willReturn($collection);

        $result = $this->foodService->query('vegetable', '');

        self::assertCount(1, $result);
        self::assertSame('Broccoli', $result[0]->getName());
        self::assertSame(FoodType::VEGETABLE, $result[0]->getType());
    }

    public function testQueryInvalidTypeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Type must be 'fruit' or 'vegetable'.");
        $this->foodService->query('meat', null);
    }

    public function testFormatReturnsGramsIntQuantity(): void
    {
        $food = new Food(7, 'Mango', FoodType::FRUIT, 1500);

        $out = $this->foodService->format($food, 'g');

        self::assertSame([
            'id' => 7,
            'name' => 'Mango',
            'type' => 'fruit',
            'quantity' => 1500,
            'unit' => 'g',
        ], $out);
        self::assertIsInt($out['quantity']);
    }

    public function testFormatReturnsKilogramsFloatQuantity(): void
    {
        $food = new Food(8, 'Pumpkin', FoodType::VEGETABLE, 1500);

        $out = $this->foodService->format($food, 'kg');

        self::assertSame(1.5, $out['quantity']);
        self::assertSame('kg', $out['unit']);
        self::assertSame(8, $out['id']);
        self::assertSame('Pumpkin', $out['name']);
        self::assertSame('vegetable', $out['type']);
        self::assertIsFloat($out['quantity']);
    }
}
