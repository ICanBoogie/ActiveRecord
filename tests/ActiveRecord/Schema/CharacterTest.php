<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use Closure;
use ICanBoogie\ActiveRecord\Schema\Character;
use LogicException;
use PHPUnit\Framework\TestCase;

final class CharacterTest extends TestCase
{
    /**
     * @dataProvider provideInvalid
     */
    public function testInvalid(string $message, Closure $new): void
    {
        $this->expectException(LogicException::class);
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
                "The maximum size for fixed character is 255, given: 256",
                fn() => new Character(size: 256, fixed: true)
            ],

            [
                "The maximum size for fixed character is 65535, given: 65536",
                fn() => new Character(size: Character::MAX_SIZE + 1)
            ],

            [
                "Collate does not apply to binary types",
                fn() => new Character(binary: true, collate: " utf8_general_ci")
            ],

        ];
    }
}
