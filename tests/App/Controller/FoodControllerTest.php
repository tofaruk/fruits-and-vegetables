<?php
declare(strict_types=1);

namespace App\Tests\App\Controller;

use App\Controller\FoodController;
use App\Model\Food;
use App\Model\FoodType;
use App\Service\FoodServiceInterface;
use App\Request\FoodAddRequestInputType;
use App\Request\FoodQueryRequestType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FoodControllerTest extends TestCase
{
    private FoodServiceInterface $serviceMock;

    private FoodController $controller;

    protected function setUp(): void
    {
        $this->serviceMock =  $this->createMock(FoodServiceInterface::class);
        $this->controller = new FoodController($this->serviceMock);
    }

    public function testSearchListsAndFormatsResultsWithQueryParams(): void
    {
        // Arrange request
        $query = new FoodQueryRequestType(  'fruit', 'ap', 'kg');

        $foods = [
            new Food(1, 'Apple', FoodType::FRUIT, 150),
            new Food(2, 'Apricot', FoodType::FRUIT, 50),
        ];

        $this->serviceMock
            ->expects(self::once())
            ->method('query')
            ->with('fruit', 'ap')
            ->willReturn($foods);

        // format() is called for each item with the requested unit
        $formatCalls = [];
        $this->serviceMock
            ->expects(self::exactly(2))
            ->method('format')
            ->willReturnCallback(function (Food $food, string $unit) use (&$formatCalls) {
                $formatCalls[] = [$food->getId(), $unit];
                // Return a shaped array that would go into JSON
                return [
                    'id' => $food->getId(),
                    'name' => $food->getName(),
                    'type' => $food->getType()->value,
                    'quantity' => $food->getGrams(),
                    'unit' => $unit,
                ];
            });

        // Act
        $response = $this->controller->search($query);

        // Assert
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $payload = json_decode($response->getContent() ?: '[]', true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertCount(2, $payload);
        self::assertSame([[1, 'kg'], [2, 'kg']], $formatCalls);
        self::assertSame('Apple', $payload[0]['name']);
        self::assertSame('fruit', $payload[0]['type']);
        self::assertSame('kg', $payload[0]['unit']);
    }

    public function testSearchDefaultsUnitToGramsWhenMissing(): void
    {
        $query = new FoodQueryRequestType(  'vegetable', 'car');

        $foods = [
            new Food(5, 'Carrot', FoodType::VEGETABLE, 80)
        ];

        $this->serviceMock
            ->expects(self::once())
            ->method('query')
            ->with('vegetable', 'car')
            ->willReturn($foods);

        $unitsSeen = [];
        $this->serviceMock
            ->expects(self::once())
            ->method('format')
            ->willReturnCallback(function (Food $food, string $unit) use (&$unitsSeen) {
                $unitsSeen[] = $unit;
                return [
                    'id' => $food->getId(),
                    'name' => $food->getName(),
                    'unit' => $unit,
                ];
            });

        $response = $this->controller->search($query);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(['g'], $unitsSeen);
    }

    public function testSearchAllowsNullTypeAndNullQuery(): void
    {
        $foods = [
            new Food(7, 'Pear', FoodType::FRUIT, 120)
        ];

        // Expect nulls to be passed through
        $this->serviceMock
            ->expects(self::once())
            ->method('query')
            ->with(null, null)
            ->willReturn($foods);

        $this->serviceMock
            ->expects(self::once())
            ->method('format')
            ->willReturn(
                ['id' => 7, 'name' => 'Pear', 'unit' => 'g']
            );

        $res = $this->controller->search(new FoodQueryRequestType());

        self::assertSame(Response::HTTP_OK, $res->getStatusCode());
        $data = json_decode(
            $res->getContent() ?: '[]',
            true,
            flags: JSON_THROW_ON_ERROR
        );
        self::assertCount(1, $data);
        self::assertSame('Pear', $data[0]['name']);
    }

    public function testDeleteRemovesItemAndReturnsNoContent(): void
    {
        $this->serviceMock
            ->expects(self::once())
            ->method('remove')
            ->with(42);

        $response = $this->controller->delete(42);

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertSame('{}', $response->getContent());
    }

    public function testAddCreatesItemAndReturnsCreatedWithGrams(): void
    {
        $input = new FoodAddRequestInputType('Banana', 'fruit', 2, 'kg');

        $saved = new Food(11, 'Banana', FoodType::FRUIT, 2000);

        $this->serviceMock
            ->expects(self::once())
            ->method('add')
            ->with('Banana', 'fruit', 2, 'kg')
            ->willReturn($saved);

        $this->serviceMock
            ->expects(self::once())
            ->method('format')
            ->with($saved, 'g')
            ->willReturn([
                'id' => 11,
                'name' => 'Banana',
                'type' => 'fruit',
                'quantity' => 2000,
                'unit' => 'g',
            ]);

        $response = $this->controller->add($input);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $payload = json_decode($response->getContent() ?: '{}', true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(11, $payload['id']);
        self::assertSame('g', $payload['unit']);
    }

}
