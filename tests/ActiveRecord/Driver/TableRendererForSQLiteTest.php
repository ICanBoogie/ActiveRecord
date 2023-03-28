<?php

namespace Test\ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Driver\TableRendererForSQLite;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Boolean;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Index;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\Text;
use ICanBoogie\ActiveRecord\Schema2;
use PHPUnit\Framework\TestCase;

final class TableRendererForSQLiteTest extends TestCase
{
    /**
     * @dataProvider provideRender
     */
    public function test_render(Schema2 $schema, string $expected): void
    {
        $prefixed_table_name = 'tblSample';

        $renderer = new TableRendererForSQLite();
        $actual = $renderer->render($schema, $prefixed_table_name);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array<array{ Schema2, string }>
     */
    public static function provideRender(): array
    {
        return [

            [
                new Schema2(
                    columns: [
                        'dance_session_id' => new Serial(),
                        'name' => new Character(),
                        'slug' => new Character(unique: true),
                        'description' => new Text(null: true),
                        'number_of_people' => new Integer(default: 0),
                    ],
                    primary: 'dance_session_id',
                ),
                <<<SQL
                CREATE TABLE tblSample (
                dance_session_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT NULL,
                number_of_people INTEGER(4) NOT NULL DEFAULT 0
                );
                SQL,
            ],

            [
                new Schema2(
                    columns: [
                        'location_id' => new BelongsTo('locations'),
                        'person_id' => new Serial(),
                        'person_uid' => new Character(11, fixed: true, unique: true),
                        'dance_session_id' => new BelongsTo('dance_sessions', null: true),
                    ],
                    primary: 'person_id',
                ),
                <<<SQL
                CREATE TABLE tblSample (
                location_id INTEGER NOT NULL,
                person_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL UNIQUE,
                person_uid CHAR(11) NOT NULL UNIQUE,
                dance_session_id INTEGER NULL
                );
                SQL,
            ],

            'Contacts' => [
                new Schema2(
                    columns: [
                        'first_name' => new Character(),
                        'last_name' => new Character(),
                        'email' => new Character(unique: true),
                    ],
                    indexes: [
                        new Index([ 'first_name', 'last_name' ], name: 'name'),
                    ]
                ),
                <<<SQL
                CREATE TABLE tblSample (
                first_name VARCHAR(255) NOT NULL,
                last_name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE
                );

                CREATE INDEX name ON tblSample (first_name, last_name);
                SQL,
            ],

            'Contacts Nameless Index' => [
                new Schema2(
                    columns: [
                        'first_name' => new Character(),
                        'last_name' => new Character(),
                        'email' => new Character(unique: true),
                    ],
                    indexes: [
                        new Index([ 'first_name', 'last_name' ]),
                    ]
                ),
                <<<SQL
                CREATE TABLE tblSample (
                first_name VARCHAR(255) NOT NULL,
                last_name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE
                );

                CREATE INDEX first_name_last_name ON tblSample (first_name, last_name);
                SQL,
            ],

            'PersonEquipment' => [
                new Schema2(
                    columns: [
                        'person_id' => new BelongsTo('persons'),
                        'equipment_id' => new BelongsTo('equipment_id'),
                    ],
                    primary: [ 'person_id', 'equipment_id' ]
                ),
                <<<SQL
                CREATE TABLE tblSample (
                person_id INTEGER NOT NULL,
                equipment_id INTEGER NOT NULL,

                PRIMARY KEY (person_id, equipment_id)
                );
                SQL,
            ],

            'Article' => [
                new Schema2(
                    columns: [
                        'nid' => new Serial(),
                        'title' => new Character(),
                        'active' => new Boolean(),
                    ],
                    indexes: [
                        new Index('active', name: 'idx_active')
                    ]
                ),
                <<<SQL
                CREATE TABLE tblSample (
                nid INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                active BOOLEAN NOT NULL
                );

                CREATE INDEX idx_active ON tblSample (active);
                SQL,
            ],

        ];
    }
}
