<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\Decimal;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

final class DecimalTest extends TestCase
{
    public function testExport(): void
    {
        $expected = new Decimal(
            precision: 9,
            scale: 3,
            approximate: true,
            null: true,
            default: "123.456",
            unique: true,
        );

        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }
}
