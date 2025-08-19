<?php
declare(strict_types=1);

namespace App\Tests\App\Repository;

use App\Model\Food;
use App\Model\FoodCollection;
use App\Model\FoodType;
use App\Repository\FoodRepositoryInterface;
use App\Repository\RedisFoodRepository;
use PHPUnit\Framework\TestCase;
use Redis;

class RedisFoodRepositoryTest extends TestCase
{
    private Redis $redisMock;

    private FoodRepositoryInterface $redisFoodRepository;

    protected function setUp(): void
    {
        $this->redisMock = $this->createMock(Redis::class);
        $this->redisFoodRepository = new RedisFoodRepository($this->redisMock);
    }

    public function testSaveAssignsIdPersistsAndIndexesByType(): void
    {
        $food = new Food(null, 'Apple', FoodType::FRUIT, 150);

        $this->redisMock
            ->expects(self::once())
            ->method('incr')
            ->with('food:seq')
            ->willReturn(1);

        // capture sAdd calls
        $sAddCalls = [];
        $this->redisMock
            ->expects(self::exactly(2))
            ->method('sAdd')
            ->willReturnCallback(function (...$args) use (&$sAddCalls) {
                $sAddCalls[] = $args;
                return 1;
            });

        $this->redisMock
            ->expects(self::once())
            ->method('set')
            ->with(
                'food:1',
                json_encode([
                    'id' => 1,
                    'name' => 'Apple',
                    'type' => 'fruit',
                    'grams' => 150,
                ], \JSON_THROW_ON_ERROR)
            )
            ->willReturn(true);

        $savedFood = $this->redisFoodRepository->save($food);

        self::assertSame(1, $savedFood->getId());
        self::assertSame('Apple', $savedFood->getName());
        self::assertSame(FoodType::FRUIT, $savedFood->getType());
        self::assertSame(150, $savedFood->getGrams());

        // assert the sAdd argument sequences
        self::assertSame([
            ['food:ids', '1'],
            ['food:type:fruit', '1'],
        ], $sAddCalls, 'sAdd should be called for ids and type set with the new id');
    }

    public function testDeleteRemovesKeyAndSetMemberships(): void
    {
        $json = json_encode([
            'id' => 2,
            'name' => 'Cucumber',
            'type' => 'vegetable',
            'grams' => 120,
        ], \JSON_THROW_ON_ERROR);

        $this->redisMock
            ->expects(self::once())
            ->method('get')
            ->with('food:2')
            ->willReturn($json);

        $this->redisMock
            ->expects(self::once())
            ->method('del')
            ->with('food:2')
            ->willReturn(1);

        // capture sRem calls
        $sRemCalls = [];
        $this->redisMock
            ->expects(self::exactly(2))
            ->method('sRem')
            ->willReturnCallback(function (...$args) use (&$sRemCalls) {
                $sRemCalls[] = $args;
                return 1;
            });

        $this->redisFoodRepository->delete(2);

        self::assertSame([
            ['food:ids', '2'],
            ['food:type:vegetable', '2'],
        ], $sRemCalls, 'sRem should be called for ids and type set with the id');
    }


    public function testAllHandlesEmptySetWithoutPipeline(): void
    {
        $this->redisMock->expects(self::once())
            ->method('sMembers')
            ->with('food:ids')
            ->willReturn([]);

        $collection = $this->redisFoodRepository->all();
        self::assertInstanceOf(FoodCollection::class, $collection);
        self::assertCount(0, iterator_to_array($collection));
    }

    public function testAllFruitsUsesTypeSet(): void
    {
        $this->redisMock->expects(self::once())
            ->method('sMembers')
            ->with('food:type:fruit')
            ->willReturn(['3']);

        $pipe = $this->createMock(Redis::class);
        $this->redisMock->expects(self::once())
            ->method('multi')
            ->with(Redis::PIPELINE)
            ->willReturn($pipe);

        $getKeys = [];
        $pipe->expects(self::once())
            ->method('get')
            ->willReturnCallback(function (string $key) use (&$getKeys, $pipe) {
                $getKeys[] = $key;
                return $pipe;
            });

        $row = json_encode(
            ['id' => 3, 'name' => 'Pear', 'type' => 'fruit', 'grams' => 120],
            \JSON_THROW_ON_ERROR
        );
        $pipe->expects(self::once())->method('exec')->willReturn([$row]);

        $collection = $this->redisFoodRepository->allFruits();
        self::assertSame(['food:3'], $getKeys);

        $items = iterator_to_array($collection);
        self::assertCount(1, $items);
        self::assertSame('Pear', $items[0]->getName());
        self::assertSame(FoodType::FRUIT, $items[0]->getType());
    }

    public function testAllVegetables_UsesTypeSet(): void
    {
        $this->redisMock->expects(self::once())
            ->method('sMembers')
            ->with('food:type:vegetable')
            ->willReturn(['10']);

        $pipe = $this->createMock(Redis::class);
        $this->redisMock->expects(self::once())
            ->method('multi')
            ->with(Redis::PIPELINE)
            ->willReturn($pipe);

        $getKeys = [];
        $pipe->expects(self::once())
            ->method('get')
            ->willReturnCallback(function (string $key) use (&$getKeys, $pipe) {
                $getKeys[] = $key;
                return $pipe;
            });

        $row = json_encode(
            ['id' => 10, 'name' => 'Broccoli', 'type' => 'vegetable', 'grams' => 300],
            \JSON_THROW_ON_ERROR
        );
        $pipe->expects(self::once())->method('exec')->willReturn([$row]);

        $collection = $this->redisFoodRepository->allVegetables();
        self::assertSame(['food:10'], $getKeys);

        $items = iterator_to_array($collection);
        self::assertCount(1, $items);
        self::assertSame('Broccoli', $items[0]->getName());
        self::assertSame(FoodType::VEGETABLE, $items[0]->getType());
    }
}
