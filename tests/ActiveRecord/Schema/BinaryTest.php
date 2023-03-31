<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\Binary;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

final class BinaryTest extends TestCase
{
    public function testExport(): void
    {
        $expected = new Binary(
            size: 250,
            fixed: true,
            null: true,
            default: "madonna",
            unique: true,
        );

        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }
}
