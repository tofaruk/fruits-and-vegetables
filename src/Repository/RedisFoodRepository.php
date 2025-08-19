<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Food;
use App\Model\FoodCollection;
use App\Model\FoodType;
use Redis;

/**
 * Redis repository for Food.
 * Keys:
 *  - food:{id} -> JSON row
 *  - food:seq  -> autoincrement
 *  - food:ids  -> Set of all IDs
 *  - food:type:{fruit|vegetable} -> Set of IDs by type
 */
class RedisFoodRepository implements FoodRepositoryInterface
{
    /**
     * @param Redis $redis
     */
    public function __construct(private Redis $redis) {}

    /** @inheritDoc */
    public function save(Food $food): Food
    {

        $id = $food->getId();
        if ($id === null) {
            $id = (int) $this->redis->incr('food:seq');
            $food->setId($id);
            $this->redis->sAdd('food:ids', (string) $id);
        }

        $payload = $this->encode([
            'id'    => $food->getId(),
            'name'  => $food->getName(),
            'type'  => $food->getType()->value,
            'grams' => $food->getGrams(),
        ]);

        $this->redis->set($this->key($id), $payload);
        $this->redis->sAdd($this->typeKey($food->getType()), (string) $id);

        return $food;
    }

    /** @inheritDoc */
    public function delete(int $id): void
    {
        $json = $this->redis->get($this->key($id));
        if ($json === false) {
            return;
        }
        $food = $this->hydrate($json);

        $this->redis->del($this->key($id));
        $this->redis->sRem('food:ids', (string) $id);
        $this->redis->sRem($this->typeKey($food->getType()), (string) $id);
    }

    /** @inheritDoc */
    public function all(): FoodCollection
    {
        $ids = $this->redis->sMembers('food:ids') ?: [];
        return new FoodCollection(...$this->hydrateMany($ids));
    }

    /** @inheritDoc */
    public function allFruits(): FoodCollection
    {
        $ids = $this->redis->sMembers($this->typeKey(FoodType::FRUIT)) ?: [];
        return new FoodCollection(...$this->hydrateMany($ids));
    }

    /** @inheritDoc */
    public function allVegetables(): FoodCollection
    {
        $ids = $this->redis->sMembers($this->typeKey(FoodType::VEGETABLE)) ?: [];
        return new FoodCollection(...$this->hydrateMany($ids));
    }

    /** @param list<string|int> $ids @return list<Food> */
    private function hydrateMany(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $pipe = $this->redis->multi(Redis::PIPELINE);
        foreach ($ids as $id) {
            $pipe->get($this->key((int) $id));
        }
        $rows = $pipe->exec() ?: [];

        $out = [];
        foreach ($rows as $json) {
            if (is_string($json)) {
                $out[] = $this->hydrate($json);
            }
        }
        return $out;
    }

    /** Build primary key for an ID. */
    private function key(int $id): string
    {
        return "food:{$id}";
    }

    /** Build set key for a type. */
    private function typeKey(FoodType $type): string
    {
        return "food:type:{$type->value}";
    }

    /** @return array{id:int,name:string,type:string,grams:int} */
    private function decode(string $json): array
    {
        /** @var array{id:int,name:string,type:string,grams:int} $decoded */
        $decoded = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);
        return $decoded;
    }

    /** JSON-encode with exceptions on failure. */
    private function encode(array $row): string
    {
        return json_encode($row, \JSON_THROW_ON_ERROR);
    }

    /** Turn a stored JSON row into a Food entity. */
    private function hydrate(string $json): Food
    {
        $d = $this->decode($json);

        return new Food(
            (int) $d['id'],
            (string) $d['name'],
            FoodType::fromString((string) $d['type']),
            (int) $d['grams']
        );
    }
}
