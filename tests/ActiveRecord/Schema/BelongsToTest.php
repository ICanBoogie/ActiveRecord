<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Location;
use Test\ICanBoogie\SetStateHelper;

final class BelongsToTest extends TestCase
{
    public function testExport(): void
    {
        $expected = new BelongsTo(
            associate: Location::class,
            size: Integer::SIZE_BIG,
            unsigned: true,
            null: true,
            unique: true,
            as: 'location',
            on_delete: OnDelete::Cascade,
        );

        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }
}
