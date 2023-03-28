<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaColumn;
use ICanBoogie\ActiveRecord\SchemaIndex;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

use function iterator_to_array;

final class SchemaTest extends TestCase
{
    public function test_has_column(): void
    {
        $schema = new Schema([
            'id' => $id = SchemaColumn::serial(primary: true),
        ]);

        $this->assertTrue($schema->has_column('id'));
        $this->assertEquals($id, $schema->columns['id']);
    }

    public function test_export(): void
    {
        $schema = new Schema(
            columns: [
                'id' => SchemaColumn::serial(primary: true),
                'name' => SchemaColumn::character(32),
            ],
            indexes: [
                new SchemaIndex([ 'id', 'name' ])
            ],
        );

        $actual = SetStateHelper::export_import($schema);

        $this->assertEquals($schema, $actual);
    }

    /**
     * @dataProvider provide_test_primary
     *
     * @param array<string, SchemaColumn> $columns
     */
    public function test_primary(array $columns, mixed $expected): void
    {
        $this->assertSame($expected, (new Schema($columns))->primary);
    }

    /**
     * @return array<string, array{ array<string, SchemaColumn>, null|string|array<string> }>
     */
    public static function provide_test_primary(): array
    {
        return [

            "no primary key" => [
                [
                    'title' => new SchemaColumn('varchar'),
                ],
                null
            ],

            "simple primary key" => [
                [
                    'id' => SchemaColumn::serial(primary: true),
                    'title' => new SchemaColumn('varchar'),
                ],
                'id'
            ],

            "compound primary key" => [
                [
                    'nid' => SchemaColumn::foreign(primary: true),
                    'uid' => SchemaColumn::foreign(primary: true),
                ],
                [ 'nid', 'uid' ]
            ],

        ];
    }

    /**
     * @dataProvider provide_test_indexes
     *
     * @param SchemaIndex[] $expected
     */
    public function test_indexes(Schema $schema, array $expected): void
    {
        $this->assertEquals($expected, $schema->indexes);
    }

    /**
     * @return array<array{ Schema, SchemaIndex[] }>
     */
    public static function provide_test_indexes(): array
    {
        return [

            "no index" => [
                new Schema([
                    'id' => SchemaColumn::serial(primary: true),
                    'title' => SchemaColumn::character(),
                ]),
                []
            ],

            "a primary and a unique" => [
                new Schema([
                    'id' => SchemaColumn::foreign(primary: true),
                    'name' => SchemaColumn::character(unique: true),
                ]),
                []
            ],

            "two indexes, one of them unique " => [
                new Schema(
                    columns: [
                        'id' => SchemaColumn::serial(primary: true),
                        'country' => SchemaColumn::character(2),
                        'week' => SchemaColumn::character(8),
                        'product' => SchemaColumn::character(),
                    ],
                    indexes: [
                        new SchemaIndex([ 'country', 'week', 'product' ], unique: true),
                        new SchemaIndex([ 'week' ], name: "my_week_index"),
                    ]
                ),
                [
                    new SchemaIndex([ 'country', 'week', 'product' ], unique: true),
                    new SchemaIndex([ 'week' ], name: "my_week_index"),
                ]
            ],

        ];
    }

    /**
     * @dataProvider provide_test_filter
     *
     * @param array<string, mixed> $values
     * @param array<string, mixed> $expected
     */
    public function test_filter(array $values, array $expected): void
    {
        $schema = new Schema([
            'id' => SchemaColumn::serial(),
            'title' => SchemaColumn::character(),
        ]);

        $this->assertEquals($expected, $schema->filter_values($values));
    }

    /**
     * @return array[]
     */
    public static function provide_test_filter(): array
    {
        return [

            [
                [
                    'extraneous1' => uniqid(),
                    'extraneous2' => uniqid()
                ],
                []
            ],

            [
                [
                    'id' => 123,
                    'extraneous' => uniqid()
                ],
                [ 'id' => 123 ]
            ],

            [
                [
                    'id' => 123,
                    'title' => "ICanBoogie",
                    'extraneous' => uniqid()
                ],
                [ 'id' => 123, 'title' => "ICanBoogie" ]
            ]

        ];
    }
}
