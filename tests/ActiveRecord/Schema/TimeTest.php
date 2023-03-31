<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\Time;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

final class TimeTest extends TestCase
{
    public function testExport(): void
    {
        $expected = new Time(
            null: true,
            default: "13:50:37",
            unique: true,
        );

        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }
}
