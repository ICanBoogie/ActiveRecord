<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use Closure;
use ICanBoogie\ActiveRecord\Schema\Character;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

final class CharacterTest extends TestCase
{
    public function testExport(): void
    {
        $expected = new Character(
            size: 250,
            fixed: true,
            null: true,
            default: "madonna",
            unique: true,
            collate: "utf8",
        );

        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }

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
