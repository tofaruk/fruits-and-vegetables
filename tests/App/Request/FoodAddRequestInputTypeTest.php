<?php

namespace App\Tests\App\Request;

use App\Request\FoodAddRequestInputType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class FoodAddRequestInputTypeTest extends TestCase
{
    public function testValidPayload(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        $i = new FoodAddRequestInputType('Apple', 'fruit', 1.25, 'kg');
        $violations = $validator->validate($i);

        self::assertCount(0, $violations, (string)$violations);
    }

    public function testInvalidPayload(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        $i = new FoodAddRequestInputType('', 'meat', -2, 'lb');
        $violations = $validator->validate($i);

        self::assertGreaterThanOrEqual(3, \count($violations));
        $messages = array_map(fn($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertTrue($this->containsOneOf($messages, [
            'This value should not be blank.',
            "This value should be positive.",
            "The value you selected is not a valid choice.",
        ]));
    }

    private function containsOneOf(array $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            foreach ($haystack as $h) {
                if (str_contains($h, $n)) return true;
            }
        }
        return false;
    }
}
