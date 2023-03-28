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
use ICanBoogie\ActiveRecord\Schema\Time;
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
     * @param array<string> $expected
     *
     * @throws Throwable
     */
    public function test_create_table_and_indexes(
        string $driver_class,
        array $expected,
    ): void {
        $this->markTestSkipped("only SQLite is supported for now");

        $connection = new class (
            new ConnectionDefinition('', 'sqlite::memory:'),
            $this,
            $expected,
        ) extends Connection {
            /**
             * @param array<string> $expected
             */
            public function __construct(
                ConnectionDefinition $definition,
                private readonly TestCase $test,
                private readonly array $expected,
            ) {
                parent::__construct($definition);
            }

            private int $i = 0;

            public function exec(string $statement): bool|int
            {
                $this->test->assertEquals($this->expected[$this->i++], $statement);

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
        $driver->create_indexes($table_name, $schema);
    }

    /**
     * @return array<array{ class-string<Driver>, string[] }>
     */
    public static function provide_expected(): array
    {
        return [

            [
                MySQLDriver::class,
                [
                    <<<SQL
                    CREATE TABLE `menus` (
                        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        `uuid` CHAR(36) NOT NULL UNIQUE,
                        `country` CHAR(2) NOT NULL,
                        `week` CHAR(8) NOT NULL,
                        `product` VARCHAR(255) NOT NULL,
                        `name` VARCHAR(255) NOT NULL,
                        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ) COLLATE utf8_general_ci
                    SQL,
                    <<<SQL
                    CREATE UNIQUE INDEX `country_week_product` ON `menus` (`country`, `week`, `product`)
                    SQL,
                    <<<SQL
                    CREATE INDEX `my_week_index` ON `menus` (`week`)
                    SQL,
                ]
            ],

            [
                SQLiteDriver::class,
                [
                    <<<SQLite
                    CREATE TABLE `menus` (
                        `id` INTEGER NOT NULL,
                        `uuid` CHAR(36) NOT NULL UNIQUE,
                        `country` CHAR(2) NOT NULL,
                        `week` CHAR(8) NOT NULL,
                        `product` VARCHAR(255) NOT NULL,
                        `name` VARCHAR(255) NOT NULL,
                        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY(`id`)
                    )
                    SQLite,
                    <<<SQLite
                    CREATE UNIQUE INDEX `country_week_product` ON `menus` (`country`, `week`, `product`)
                    SQLite,
                    <<<SQLite
                    CREATE INDEX `my_week_index` ON `menus` (`week`)
                    SQLite,
                ]
            ],

        ];
    }
}
