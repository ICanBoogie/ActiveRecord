<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\Binary;
use ICanBoogie\ActiveRecord\Schema\Date;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

final class DateTest extends TestCase
{
    public function testExport(): void
    {
        $expected = new Date(
            null: true,
            default: "1977-06-06",
            unique: true,
        );

        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }
}
