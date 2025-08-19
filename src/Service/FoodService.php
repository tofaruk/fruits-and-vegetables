<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Food;
use App\Model\FoodType;
use App\Model\Unit;
use App\Repository\FoodRepositoryInterface;

/**
 * Default implementation of FoodServiceInterface.
 * Keeps business rules (units, validation, filtering) out of controllers.
 */
class FoodService implements FoodServiceInterface
{
    /**
     * @param FoodRepositoryInterface $foodRepository
     */
    public function __construct(private FoodRepositoryInterface $foodRepository) {}

    /** @inheritDoc */
    public function add(string $name, string $type, float|int $quantity, string $unit): Food
    {
        $foodType = FoodType::fromString($type);
        $u        = Unit::fromString($unit);

        $grams = (int) round($quantity * $u->toGramsFactor(), 0);
        $food  = new Food(null, $name, $foodType, $grams);

        return $this->foodRepository->save($food);
    }

    /** @inheritDoc */
    public function remove(int $id): void
    {
        $this->foodRepository->delete($id);
    }

    /** @inheritDoc */
    public function query(?string $type = null, ?string $search = null): array
    {
        $collection = match ($type) {
            null        => $this->foodRepository->all(),
            'fruit'     => $this->foodRepository->allFruits(),
            'vegetable' => $this->foodRepository->allVegetables(),
            default     => throw new \InvalidArgumentException("Type must be 'fruit' or 'vegetable'."),
        };

        return $collection->search($search)->toArray();
    }

    /** @inheritDoc */
    public function format(Food $food, string $unit = 'g'): array
    {
        $u   = Unit::fromString($unit);
        $qty = $u === Unit::G ? $food->getGrams() : $food->getGrams() / 1000;

        return [
            'id'       => $food->getId(),
            'name'     => $food->getName(),
            'type'     => $food->getType()->value,
            'quantity' => $u === Unit::G ? (int) $qty : (float) $qty,
            'unit'     => $u->value,
        ];
    }
}
