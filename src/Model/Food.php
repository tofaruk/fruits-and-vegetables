<?php
declare(strict_types=1);

namespace App\Model;

/**
 * Quantity is always stored internally in grams.
 */
class Food
{
    /**
     * @param int|null $id
     * @param string $name
     * @param FoodType $type
     * @param int $grams
     */
    public function __construct(
        private ?int $id,
        private string $name,
        private FoodType $type,
        private int $grams)
    {
    }

    /** Identifier or null when not yet persisted. */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** Assign the identifier after persistence. @return $this */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /** Food name. */
    public function getName(): string
    {
        return $this->name;
    }

    /** Fruit or Vegetable. */
    public function getType(): FoodType
    {
        return $this->type;
    }

    /** Quantity in grams. */
    public function getGrams(): int
    {
        return $this->grams;
    }
}
