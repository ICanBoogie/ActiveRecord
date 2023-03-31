<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Integer;
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
            null: true,
            unique: true,
            as: 'location',
        );

        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }
}
