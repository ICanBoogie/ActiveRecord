<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\Text;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

final class TextTest extends TestCase
{
    public function testExport(): void
    {
        $expected = new Text(
            size: Text::SIZE_MEDIUM,
            null: true,
            default: "madonna",
            unique: true,
            collate: 'utf8',
        );

        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }
}
