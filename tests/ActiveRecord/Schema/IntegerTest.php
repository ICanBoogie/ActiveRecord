<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use Closure;
use ICanBoogie\ActiveRecord\Schema\Integer;
use LogicException;
use PHPUnit\Framework\TestCase;

final class IntegerTest extends TestCase
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
                "Size must be one of the allowed ones",
                fn() => new Integer(size: 5)
            ],

            [
                "Size must be one of the allowed ones",
                fn() => new Integer(size: 9)
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
                fn() => new Integer(size: Integer::SIZE_BIG, unsigned: true, null: true, serial: true)
            ],

            [
                "A serial integer must be unique",
                fn() => new Integer(size: Integer::SIZE_BIG, unsigned: true, serial: true)
            ],

        ];
    }
}
