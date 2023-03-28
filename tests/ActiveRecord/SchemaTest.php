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
use Test\ICanBoogie\Acme\Location;
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
                't101' => new Schema\Boolean(
                    null: true,
                ),
                't102' => new Schema\Integer(
                    size: 4,
                    unsigned: true,
                    serial: false,
                    null: true,
                    unique: true,
                    default: 13,
                ),
                't103' => new Schema\Serial(
                    size: 8,
                ),
                't104' => new Schema\BelongsTo(
                    associate: Location::class,
                    size: 8,
                    null: true,
                    unique: true,
                    as: 'location',
                ),

                't201' => new Schema\Date(
                    null: true,
                    default: "1977-06-06",
                    unique: true,
                ),
                't202' => new Schema\Time(
                    null: true,
                    default: "13:30:45",
                    unique: true,
                ),
                't203' => new Schema\DateTime(
                    null: true,
                    default: "1977-06-06T13:30:45",
                    unique: true,
                ),

                't301' => new Schema\Character(
                    size: 32,
                    fixed: true,
                    null: true,
                    default: "madonna",
                    unique: true,
                    collate: 'utf8_general_ci',
                ),
                't302' => new Schema\Binary(
                    size: 32,
                    fixed: true,
                    null: true,
                    default: "madonna",
                    unique: true,
                ),
                't303' => new Schema\Text(
                    size: Schema\Text::SIZE_MEDIUM,
                    null: true,
                    default: "madonna",
                    unique: true,
                    collate: 'utf8_general_ci',
                ),
                't304' => new Schema\Blob(
                    size: Schema\Blob::SIZE_MEDIUM,
                    null: true,
                    default: "madonna",
                    unique: true,
                ),
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
