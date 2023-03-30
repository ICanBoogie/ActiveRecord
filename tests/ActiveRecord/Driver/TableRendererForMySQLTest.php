<?php

namespace Test\ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Driver\TableRendererForMySQL;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Binary;
use ICanBoogie\ActiveRecord\Schema\Blob;
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
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\Equipment;
use Test\ICanBoogie\Acme\Location;

final class TableRendererForMySQLTest extends TestCase
{
    /**
     * @dataProvider provideRender
     */
    public function test_render(Schema $schema, string $expected): void
    {
        $prefixed_table_name = 'tblSample';

        $renderer = new TableRendererForMySQL();
        $actual = $renderer->render($schema, $prefixed_table_name);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array<array{ Schema, string }>
     */
    public static function provideRender(): array
    {
        return [

            "A bit of everything" => [
                new Schema(
                    columns: [
                        'i1' => new Boolean(),
                        'i2' => new Integer(),
                        'i3' => new Integer(size: Integer::SIZE_BIG),
                        'i4' => new BelongsTo(Article::class),
                        'i5' => new BelongsTo(Article::class, null: true),
                        'i6' => new Serial(),

                        'c1' => new Character(),
                        'c2' => new Character(11, fixed: true),
                        'c3' => new Binary(),
                        'c4' => new Binary(11, fixed: true),
                        'c5' => new Text(),
                        'c6' => new Text(size: Text::SIZE_LONG),
                        'c7' => new Blob(),
                        'c8' => new Blob(size: Blob::SIZE_LONG),

                        't1' => new DateTime(),
                        't2' => new DateTime(default: DateTime::CURRENT_TIMESTAMP),
                        't3' => new Timestamp(),
                        't4' => new Timestamp(default: Timestamp::CURRENT_TIMESTAMP),
                        't5' => new Date(),
                        't6' => new Date(default: Date::CURRENT_DATE),
                        't7' => new Time(),
                        't8' => new Time(default: Time::CURRENT_TIME),
                    ],
                    primary: 'i6',
                    indexes: [
                        new Index('c1'),
                        new Index('c2', unique: true),
                        new Index('c3', unique: true),
                        new Index('c4', unique: true, name: 'idx_c4'),
                        new Index([ 'i2', 'i3' ], name: 'idx_i2'),
                    ],
                ),
                <<<SQL
                CREATE TABLE tblSample (
                i1 BOOLEAN UNSIGNED NOT NULL,
                i2 INTEGER(4) NOT NULL,
                i3 INTEGER(8) NOT NULL,
                i4 INTEGER(4) NOT NULL,
                i5 INTEGER(4) NULL,
                i6 INTEGER(4) UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
                c1 VARCHAR(255) NOT NULL,
                c2 CHAR(11) NOT NULL,
                c3 VARBINARY(255) NOT NULL,
                c4 BINARY(11) NOT NULL,
                c5 TEXT NOT NULL,
                c6 LONGTEXT NOT NULL,
                c7 BLOB NOT NULL,
                c8 LONGBLOB NOT NULL,
                t1 DATETIME NOT NULL,
                t2 DATETIME NOT NULL DEFAULT (CURRENT_TIMESTAMP),
                t3 TIMESTAMP NOT NULL,
                t4 TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP),
                t5 DATE NOT NULL,
                t6 DATE NOT NULL DEFAULT (CURRENT_DATE),
                t7 TIME NOT NULL,
                t8 TIME NOT NULL DEFAULT (CURRENT_TIME),

                PRIMARY KEY (i6),
                UNIQUE (c2),
                UNIQUE (c3)
                ) COLLATE utf8_general_ci;

                CREATE INDEX c1 ON tblSample (c1);
                CREATE UNIQUE INDEX idx_c4 ON tblSample (c4);
                CREATE INDEX idx_i2 ON tblSample (i2, i3);
                SQL,
            ],

            "MySQL: A Serial doesn't have to be the primary key" => [
                new Schema(
                    columns: [
                        'id' => new Serial(),
                    ],
                ),
                <<<SQL
                CREATE TABLE tblSample (
                id INTEGER(4) UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE
                ) COLLATE utf8_general_ci;
                SQL,
            ],

            'Multi-column named index' => [
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
                ) COLLATE utf8_general_ci;

                CREATE INDEX name ON tblSample (first_name, last_name);
                SQL,
            ],

            'Multi-column nameless index' => [
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
                ) COLLATE utf8_general_ci;

                CREATE INDEX first_name_last_name ON tblSample (first_name, last_name);
                SQL,
            ],

            'Multi-column primary key' => [
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
                equipment_id INTEGER(4) NOT NULL,
                location_id INTEGER(4) NOT NULL,
                location_hint VARCHAR(255) NULL,

                PRIMARY KEY (equipment_id, location_id)
                ) COLLATE utf8_general_ci;
                SQL,
            ],

        ];
    }
}
