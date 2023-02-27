<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord;

use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

final class SchemaColumnTest extends TestCase
{
    /**
     * @dataProvider provide_columns
     */
    public function test_to_string(SchemaColumn $column, string $expected): void
    {
        $this->assertEquals($expected, (string) $column);
    }

    /**
     * @dataProvider provide_columns
     */
    public function test_export(SchemaColumn $column): void
    {
        $actual = SetStateHelper::export_import($column);

        $this->assertEquals($column, $actual);
    }

    public function provide_columns(): array // @phpstan-ignore-line
    {
        return [

            /* BOOLEAN */

            [
                SchemaColumn::boolean(),
                "TINYINT NOT NULL"
            ],

            /* INT */

            [
                SchemaColumn::int(size: 1),
                "TINYINT NOT NULL"
            ],
            [
                SchemaColumn::int(size: 2),
                "SMALLINT NOT NULL"
            ],
            [
                SchemaColumn::int(size: 3),
                "MEDIUMINT NOT NULL"
            ],
            [
                SchemaColumn::int(size: 4),
                "INT NOT NULL"
            ],
            [
                SchemaColumn::int(size: 6),
                "INT(6) NOT NULL"
            ],
            [
                SchemaColumn::int(size: 8),
                "BIGINT NOT NULL"
            ],
            [
                SchemaColumn::int(size: SchemaColumn::SIZE_SMALL),
                "SMALLINT NOT NULL"
            ],
            [
                SchemaColumn::int(size: SchemaColumn::SIZE_BIG),
                "BIGINT NOT NULL"
            ],
            [
                SchemaColumn::int(size: SchemaColumn::SIZE_BIG, unsigned: true, null: true),
                "BIGINT UNSIGNED NULL"
            ],

            /* VARCHAR */

            [
                SchemaColumn::varchar(),
                "VARCHAR(255) NOT NULL"
            ],
            [
                SchemaColumn::varchar(size: 32),
                "VARCHAR(32) NOT NULL"
            ],
            [
                SchemaColumn::varchar(unique: true, collate: 'ascii_general_ci'),
                "VARCHAR(255) NOT NULL UNIQUE COLLATE ascii_general_ci"
            ],

            /* BLOB */

            [
                SchemaColumn::blob(size: SchemaColumn::SIZE_TINY),
                "TINYBLOB NOT NULL"
            ],
            [
                SchemaColumn::blob(size: SchemaColumn::SIZE_SMALL),
                "BLOB NOT NULL"
            ],
            [
                SchemaColumn::blob(size: SchemaColumn::SIZE_MEDIUM),
                "MEDIUMBLOB NOT NULL"
            ],
            [
                SchemaColumn::blob(size: SchemaColumn::SIZE_BIG),
                "LONGBLOB NOT NULL"
            ],

            /* TEXT */

            [
                SchemaColumn::text(size: SchemaColumn::SIZE_TINY),
                "TINYTEXT NOT NULL"
            ],
            [
                SchemaColumn::text(size: SchemaColumn::SIZE_SMALL),
                "TEXT NOT NULL"
            ],
            [
                SchemaColumn::text(size: SchemaColumn::SIZE_MEDIUM),
                "MEDIUMTEXT NOT NULL"
            ],
            [
                SchemaColumn::text(size: SchemaColumn::SIZE_BIG),
                "LONGTEXT NOT NULL"
            ],

            /* DATETIME */

            [
                SchemaColumn::datetime(default: SchemaColumn::CURRENT_TIMESTAMP),
                "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"
            ],

            /* SERIAL */

            [
                SchemaColumn::serial(),
                "BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE"
            ],
            [
                SchemaColumn::serial(primary: true),
                "BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY"
            ],

            /* FOREIGN */

            [
                SchemaColumn::foreign(),
                "BIGINT UNSIGNED NOT NULL"
            ],
            [
                SchemaColumn::foreign(null: true),
                "BIGINT UNSIGNED NULL"
            ],

        ];
    }
}
