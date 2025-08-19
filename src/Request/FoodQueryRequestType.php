<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query DTO for GET /api/food
 */
class FoodQueryRequestType
{
    /**
     * @param string|null $type
     * @param string|null $q
     * @param string|null $unit
     */
    public function __construct(
        #[Assert\Choice(['fruit', 'vegetable'])] public ?string $type = null,
        #[Assert\Length(min: 1, max: 50)] public ?string $q = null,
        #[Assert\Choice(['g', 'kg'])] public ?string $unit = 'g',
    )
    {}
}