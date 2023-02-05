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

use function iterator_to_array;

final class SchemaTest extends TestCase
{
    public function test_array_access(): void
    {
        $schema = new Schema();
        $id = SchemaColumn::serial(primary: true);
        $this->assertFalse(isset($schema['id']));
        $schema['id'] = $id;
        $this->assertTrue(isset($schema['id']));
        $this->assertEquals($id, $schema['id']);
        unset($schema['id']);
        $this->assertFalse(isset($schema['id']));
    }

    public function test_export(): void
    {
        $schema = new Schema([
            'id' => SchemaColumn::serial(primary: true),
            'name' => SchemaColumn::varchar(32),
        ]);

        $actual = SetStateHelper::export_import($schema);

        $this->assertEquals($schema, $actual);
    }

    public function test_iterator(): void
    {
        $schema = new Schema(
            $columns = [
                'id' => SchemaColumn::serial(primary: true),
                'name' => SchemaColumn::varchar(32),
            ]
        );

        $this->assertEquals($columns, $schema->columns);
        $this->assertEquals($columns, iterator_to_array($schema));
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

    public function provide_test_primary(): array
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
     * @return array[]
     */
    public function provide_test_indexes(): array
    {
        return [

            "no index" => [
                new Schema([
                    'id' => SchemaColumn::serial(primary: true),
                    'title' => SchemaColumn::varchar(),
                ]),
                []
            ],

            "a primary and a unique" => [
                new Schema([
                    'id' => SchemaColumn::foreign(primary: true),
                    'name' => SchemaColumn::varchar(unique: true),
                ]),
                []
            ],

            "two indexes, one of them unique " => [
                (new Schema([
                    'id' => SchemaColumn::serial(primary: true),
                    'country' => SchemaColumn::char(size: 2),
                    'week' => SchemaColumn::char(size: 8),
                    'product' => SchemaColumn::varchar(),
                ]))
                    ->index([ 'country', 'week', 'product' ], unique: true)
                    ->index('week', name: "my_week_index"),
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
            'title' => SchemaColumn::varchar(),
        ]);

        $this->assertEquals($expected, $schema->filter_values($values));
    }

    /**
     * @return array[]
     */
    public function provide_test_filter(): array
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
