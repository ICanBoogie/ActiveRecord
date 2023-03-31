<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\DateTime;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

final class DateTimeTest extends TestCase
{
    public function testExport(): void
    {
        $expected = new DateTime(
            null: true,
            default: "1977-06-06 13:50:37",
            unique: true,
        );

        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }
}
