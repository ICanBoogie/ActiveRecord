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
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

final class SchemaTest extends TestCase
{
    public function test_has_column(): void
    {
        $schema = new Schema([
            'id' => $id = new Schema\Serial(),
        ]);

        $this->assertTrue($schema->has_column('id'));
        $this->assertEquals($id, $schema->columns['id']);
    }

    public function test_export(): void
    {
        $schema = new Schema(
            columns: [
                'id' => new Schema\Serial(),
                'name' => new Schema\Character(32),
            ],
            primary: 'id',
            indexes: [
                new Schema\Index([ 'id', 'name' ])
            ],
        );

        $actual = SetStateHelper::export_import($schema);

        $this->assertEquals($schema, $actual);
    }

    /**
     * @dataProvider provide_test_filter
     *
     * @param array<non-empty-string, mixed> $values
     * @param array<non-empty-string, mixed> $expected
     */
    public function test_filter(array $values, array $expected): void
    {
        $schema = new Schema([
            'id' => new Schema\Serial(),
            'title' => new Schema\Character(),
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
