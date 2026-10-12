<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\Binary;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Blob;
use ICanBoogie\ActiveRecord\Schema\Boolean;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Date;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\Schema\Decimal;
use ICanBoogie\ActiveRecord\Schema\Index;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\Text;
use ICanBoogie\ActiveRecord\Schema\Time;
use ICanBoogie\ActiveRecord\Schema\Timestamp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Location;
use Test\ICanBoogie\SetStateHelper;

/**
 * Schema attributes must survive `var_export()`, which is how configs are cached.
 */
final class ExportTest extends TestCase
{
    #[DataProvider('provide_export')]
    public function test_export(object $expected): void
    {
        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return iterable<string, array{ object }>
     */
    public static function provide_export(): iterable
    {
        yield 'binary' => [
            new Binary(size: 250, fixed: true, null: true, default: "madonna", unique: true)
        ];

        yield 'blob' => [
            new Blob(size: Blob::SIZE_LONG, null: true, default: "madonna", unique: true)
        ];

        yield 'boolean null' => [ new Boolean(null: true) ];
        yield 'boolean true' => [ new Boolean(default: true) ];
        yield 'boolean false' => [ new Boolean(default: false) ];

        yield 'character' => [
            new Character(size: 250, fixed: true, null: true, default: "madonna", unique: true, collate: "utf8")
        ];

        yield 'date' => [ new Date(null: true, default: "1977-06-06", unique: true) ];
        yield 'datetime' => [ new DateTime(null: true, default: "1977-06-06 13:50:37", unique: true) ];

        yield 'decimal' => [
            new Decimal(
                precision: 9,
                scale: 3,
                approximate: true,
                null: true,
                default: "123.456",
                unique: true
            )
        ];

        yield 'index' => [ new Index(columns: [ 'one', 'two' ], unique: true, name: "madonna") ];

        yield 'integer' => [
            new Integer(
                size: Integer::SIZE_BIG,
                unsigned: true,
                serial: false,
                null: true,
                unique: true,
                default: 123
            )
        ];

        yield 'serial' => [ new Serial(size: Integer::SIZE_BIG) ];

        yield 'text' => [
            new Text(size: Text::SIZE_MEDIUM, null: true, default: "madonna", unique: true, collate: 'utf8')
        ];

        yield 'time' => [ new Time(null: true, default: "13:50:37", unique: true) ];
        yield 'timestamp' => [ new Timestamp(null: true, default: "1977-06-06 13:50:37", unique: true) ];

        yield 'belongs to' => [
            new BelongsTo(
                associate: Location::class,
                size: Integer::SIZE_BIG,
                unsigned: true,
                null: true,
                unique: true,
                as: 'location',
                on_delete: OnDelete::Cascade,
            )
        ];
    }
}
