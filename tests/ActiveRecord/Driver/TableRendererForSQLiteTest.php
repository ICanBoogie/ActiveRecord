<?php

namespace Test\ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Driver\TableRendererForSQLite;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Boolean;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Date;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\Schema\Index;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\Text;
use ICanBoogie\ActiveRecord\Schema\Time;
use ICanBoogie\ActiveRecord\Schema\Timestamp;
use PDO;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\Equipment;
use Test\ICanBoogie\Acme\Location;

final class TableRendererForSQLiteTest extends TestCase
{
    /**
     * @dataProvider provideRender
     */
    public function test_render(Schema $schema, string $expected): void
    {
        $prefixed_table_name = 'tblSample';

        $renderer = new TableRendererForSQLite();
        $actual = $renderer->render($schema, $prefixed_table_name);

        $this->assertEquals($expected, $actual);

        $pdo = new PDO('sqlite::memory:');
        $pdo->exec($actual);
    }

    /**
     * @return array<array{ Schema, string }>
     */
    public static function provideRender(): array
    {
        return [

            [
                new Schema(
                    columns: [
                        'i1' => new Boolean(),
                        'i2' => new Integer(),
                        'i3' => new Integer(size: Integer::SIZE_BIG),
                        'i4' => new BelongsTo(Article::class),
                        'i5' => new BelongsTo(Article::class, null: true),
                        'i6' => new Serial(),

                        'c1' => new Character(),
                        'c2' => new Character(fixed: true),
                        'c3' => new Character(binary: true),
                        'c4' => new Character(fixed: true, binary: true),
                        'c5' => new Text(),
                        'c6' => new Text(size: Text::SIZE_LONG),

                        't1' => new DateTime(),
                        't2' => new DateTime(default: DateTime::CURRENT_TIMESTAMP),
                        't3' => new Timestamp(),
                        't4' => new Timestamp(default: Timestamp::CURRENT_TIMESTAMP),
                        't5' => new Date(),
                        't6' => new Date(default: Date::CURRENT_DATE),
                        't7' => new Time(),
                        't8' => new Time(default: Time::CURRENT_TIME),
                    ],
                ),
                <<<SQL
                CREATE TABLE tblSample (
                i1 BOOLEAN NOT NULL,
                i2 INTEGER(4) NOT NULL,
                i3 INTEGER(8) NOT NULL,
                i4 INTEGER NOT NULL,
                i5 INTEGER NULL,
                i6 INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL UNIQUE,
                c1 VARCHAR(255) NOT NULL,
                c2 CHAR(255) NOT NULL,
                c3 VARBINARY(255) NOT NULL,
                c4 BINARY(255) NOT NULL,
                c5 TEXT NOT NULL,
                c6 LONGTEXT NOT NULL,
                t1 DATETIME NOT NULL,
                t2 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                t3 TIMESTAMP NOT NULL,
                t4 TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                t5 DATE NOT NULL,
                t6 DATE NOT NULL DEFAULT CURRENT_DATE,
                t7 TIME NOT NULL,
                t8 TIME NOT NULL DEFAULT CURRENT_TIME
                );
                SQL,
            ],

            [
                new Schema(
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
                new Schema(
                    columns: [
                        'location_id' => new BelongsTo(Location::class),
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
                new Schema(
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
                new Schema(
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

            'EquipmentLocation' => [
                new Schema(
                    columns: [
                        'equipment_id' => new BelongsTo(Equipment::class),
                        'location_id' => new BelongsTo(Location::class),
                        'location_hint' => new Character(null: true),
                    ],
                    primary: [ 'equipment_id', 'location_id' ]
                ),
                <<<SQL
                CREATE TABLE tblSample (
                equipment_id INTEGER NOT NULL,
                location_id INTEGER NOT NULL,
                location_hint VARCHAR(255) NULL,

                PRIMARY KEY (equipment_id, location_id)
                );
                SQL,
            ],

            'Article' => [
                new Schema(
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
