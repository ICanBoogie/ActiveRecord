<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\Index;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

final class IndexTest extends TestCase
{
    public function testExport(): void
    {
        $expected = new Index(
            columns: [ 'one', 'two' ],
            unique: true,
            name: "madonna",
        );

        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }
}
