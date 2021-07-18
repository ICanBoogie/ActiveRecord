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

final class SchemaColumnTest extends TestCase
{
    /**
     * @dataProvider provide_test_to_string
     */
    public function test_to_string(SchemaColumn $column, string $expected): void
    {
        $this->assertEquals($expected, (string) $column);
    }

    /**
     * @return array[]
     */
    public function provide_test_to_string(): array
    {
        return [

            [
                SchemaColumn::int(size: 'tiny'),
                "TINYINT NOT NULL"
            ],
            [
                SchemaColumn::int(size: 'small'),
                "SMALLINT NOT NULL"
            ],
            [
                SchemaColumn::int(size: 'big'),
                "BIGINT NOT NULL"
            ],
            [
                SchemaColumn::int(size: 'big', unsigned: true, null: true),
                "BIGINT UNSIGNED NULL"
            ],
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
            [
                SchemaColumn::datetime(default: SchemaColumn::CURRENT_TIMESTAMP),
                "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"
            ],
            [
                SchemaColumn::int('big'),
                "BIGINT NOT NULL"
            ],
            [
                SchemaColumn::serial(),
                "BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE"
            ],
            [
                SchemaColumn::serial(primary: true),
                "BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY"
            ],
        ];
    }
}
