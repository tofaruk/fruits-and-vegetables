<?php
declare(strict_types=1);

namespace App\Model;

/**
 * Food kind discriminator.
 */
enum FoodType: string
{
    case FRUIT = 'fruit';
    case VEGETABLE = 'vegetable';

    /**
     * Convert a string to FoodType.
     *
     * @throws \InvalidArgumentException When value is not a valid type.
     */
    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));
        return match ($value) {
            self::FRUIT->value => self::FRUIT,
            self::VEGETABLE->value => self::VEGETABLE,
            default => throw new \InvalidArgumentException("Type must be 'fruit' or 'vegetable'."),
        };
    }
}
