<?php
declare(strict_types=1);

namespace App\Model;

/**
 * Supported input/output units for quantity.
 */
enum Unit: string
{
    case G = 'g';
    case KG = 'kg';

    /**
     * Parse a string to Unit.
     *
     * @throws \InvalidArgumentException When unit is unsupported.
     */
    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));
        return match ($value) {
            self::G->value => self::G,
            self::KG->value => self::KG,
            default => throw new \InvalidArgumentException("Unit must be 'g' or 'kg'."),
        };
    }

    /**
     * Multiplicative factor to convert a value expressed in this unit to grams.
     */
    public function toGramsFactor(): int
    {
        return match ($this) {
            self::G => 1,
            self::KG => 1000,
        };
    }
}
