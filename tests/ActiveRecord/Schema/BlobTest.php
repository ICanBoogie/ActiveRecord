<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\Blob;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

final class BlobTest extends TestCase
{
    public function testExport(): void
    {
        $expected = new Blob(
            size: Blob::SIZE_LONG,
            null: true,
            default: "madonna",
            unique: true,
        );

        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }
}
