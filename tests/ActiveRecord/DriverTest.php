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

use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use ICanBoogie\ActiveRecord\Connection;
use ICanBoogie\ActiveRecord\Driver\MySQLDriver;
use ICanBoogie\ActiveRecord\Driver\SQLiteDriver;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Driver;
use Throwable;

final class DriverTest extends TestCase
{
    /**
     * @dataProvider provide_expected
     *
     * @param class-string<Driver> $driver_class
     * @param string $expected
     *
     * @throws Throwable
     */
    public function test_create_table_and_indexes(
        string $driver_class,
        string $expected,
    ): void {
        $connection = new class (
            new ConnectionDefinition('', 'sqlite::memory:'),
            $this,
            $expected,
        ) extends Connection {
            public function __construct(
                ConnectionDefinition $definition,
                private readonly TestCase $test,
                private readonly string $expected,
            ) {
                parent::__construct($definition);
            }

            public function exec(string $statement): bool|int
            {
                $this->test->assertEquals($this->expected, $statement);

                return 1;
            }
        };

        $schema = (new SchemaBuilder())
            ->add_serial('id', primary: true)
            ->add_character('uuid', size: 36, fixed: true, unique: true)
            ->add_character('country', size: 2, fixed: true)
            ->add_character('week', size: 8, fixed: true)
            ->add_character('product')
            ->add_character('name')
            ->add_timestamp('created_at', default: DateTime::CURRENT_TIMESTAMP)
            ->add_index([ 'country', 'week', 'product' ], unique: true)
            ->add_index('week', name: 'my_week_index')
            ->build();

        $table_name = "menus";
        $driver = new $driver_class(fn() => $connection);
        $driver->create_table($table_name, $schema);
    }

    /**
     * @return array<array{ class-string<Driver>, string }>
     */
    public static function provide_expected(): array
    {
        return [

            [
                MySQLDriver::class,
                <<<MySQL
                CREATE TABLE menus (
                id INTEGER(4) UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
                uuid CHAR(36) NOT NULL UNIQUE,
                country CHAR(2) NOT NULL,
                week CHAR(8) NOT NULL,
                product VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP),

                PRIMARY KEY (id),
                UNIQUE (country, week, product)
                ) COLLATE utf8_general_ci;

                CREATE INDEX my_week_index ON menus (week);
                MySQL,
            ],

            [
                SQLiteDriver::class,
                <<<SQLite
                CREATE TABLE menus (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL UNIQUE,
                uuid CHAR(36) NOT NULL UNIQUE,
                country CHAR(2) NOT NULL,
                week CHAR(8) NOT NULL,
                product VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

                UNIQUE (country, week, product)
                );

                CREATE INDEX my_week_index ON menus (week);
                SQLite,
            ],

        ];
    }
}
