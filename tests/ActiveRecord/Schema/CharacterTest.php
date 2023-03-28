<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use Closure;
use ICanBoogie\ActiveRecord\Schema\Character;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CharacterTest extends TestCase
{
    /**
     * @dataProvider provideInvalid
     */
    public function testInvalid(string $message, Closure $new): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $new();
    }

    /**
     * @return array<array{ non-empty-string, Closure }>
     */
    public static function provideInvalid(): array
    {
        return [

            [
                "For fixed character, the size must be less than 255, given: 256",
                fn() => new Character(size: 256, fixed: true)
            ],

        ];
    }
}
