<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use Closure;
use ICanBoogie\ActiveRecord\Schema\Integer;
use LogicException;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

final class IntegerTest extends TestCase
{
    public function testExport(): void
    {
        $expected = new Integer(
            size: Integer::SIZE_BIG,
            unsigned: true,
            serial: false,
            null: true,
            unique: true,
            default: 123,
        );

        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }

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
                "Size must be one of the allowed ones",
                fn() => new Integer(size: 5) // @phpstan-ignore-line
            ],

            [
                "Size must be one of the allowed ones",
                fn() => new Integer(size: 9) // @phpstan-ignore-line
            ],

            // serial

            [
                "A serial integer must be at least 2 bytes",
                fn() => new Integer(size: Integer::SIZE_TINY, serial: true)
            ],

            [
                "A serial integer must be unsigned",
                fn() => new Integer(size: Integer::SIZE_BIG, serial: true)
            ],

            [
                "A serial integer cannot be nullable",
                fn() => new Integer(size: Integer::SIZE_BIG, unsigned: true, serial: true, null: true)
            ],

            [
                "A serial integer must be unique",
                fn() => new Integer(size: Integer::SIZE_BIG, unsigned: true, serial: true)
            ],

        ];
    }
}
