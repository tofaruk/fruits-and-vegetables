<?php

namespace App\Tests\App\Model;

use App\Model\Unit;
use PHPUnit\Framework\TestCase;


class UnitTest extends TestCase
{
    public function testFromString(): void
    {
        self::assertSame(Unit::G, Unit::fromString('g'));
        self::assertSame(Unit::KG, Unit::fromString('kg'));
        self::assertSame(Unit::G, Unit::fromString('G'));
        self::assertSame(Unit::KG, Unit::fromString('KG'));
    }

    public function testFromStringInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Unit::fromString('lb');
    }

    public function testToGramsFactor(): void
    {
        self::assertSame(1, Unit::G->toGramsFactor());
        self::assertSame(1000, Unit::KG->toGramsFactor());
    }
}
