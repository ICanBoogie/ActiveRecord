<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Serial;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

use function get_object_vars;

final class SerialTest extends TestCase
{
    public function testExport(): void
    {
        $expected = new Serial(
            size: Integer::SIZE_BIG,
        );

        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider provideInstance
     */
    public function testInstance(Serial $actual, Integer $expected): void
    {
        $this->assertEquals(
            get_object_vars($expected),
            get_object_vars($actual)
        );
    }

    /**
     * @return array<array{ Serial, Integer }>
     */
    public static function provideInstance(): array
    {
        return [ // @phpstan-ignore-line

            [
                new Serial(),
                new Integer(size: Integer::SIZE_REGULAR, unsigned: true, serial: true, unique: true),
            ],

            [
                new Serial(size: Integer::SIZE_SMALL),
                new Integer(size: Integer::SIZE_SMALL, unsigned: true, serial: true, unique: true),
            ],

        ];
    }
}
