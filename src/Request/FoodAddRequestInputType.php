<?php
declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO for POST /api/food
 */
class FoodAddRequestInputType
{
    /**
     * @param string     $name     Non-empty name.
     * @param string     $type     'fruit' or 'vegetable'.
     * @param int|float  $quantity Positive number.
     * @param string     $unit     'g' or 'kg'.
     */
    public function __construct(
        #[Assert\NotBlank] public string $name,
        #[Assert\Choice(['fruit','vegetable'])] public string $type,
        #[Assert\Positive] public int|float $quantity,
        #[Assert\Choice(['g','kg'])] public string $unit = 'g',
    ) {}
}
