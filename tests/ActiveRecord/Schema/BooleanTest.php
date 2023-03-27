<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\Boolean;
use ICanBoogie\ActiveRecord\Schema\Integer;
use PHPUnit\Framework\TestCase;

use function get_object_vars;

final class BooleanTest extends TestCase
{
    /**
     * @dataProvider provideInstance
     */
    public function testInstance(Boolean $actual, Integer $expected): void
    {
        $this->assertEquals(
            get_object_vars($expected),
            get_object_vars($actual)
        );
    }

    /**
     * @return array<array{ Boolean, Integer }>
     */
    public static function provideInstance(): array
    {
        return [ // @phpstan-ignore-line

            [
                new Boolean(),
                new Integer(size: Integer::SIZE_TINY, unsigned: true),
            ],

            [
                new Boolean(null: true),
                new Integer(size: Integer::SIZE_TINY, unsigned: true, null: true),
            ],

        ];
    }
}
