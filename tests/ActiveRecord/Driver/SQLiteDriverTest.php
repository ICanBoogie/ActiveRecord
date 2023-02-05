<?php

namespace ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Connection;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaColumn;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Throwable;

final class SQLiteDriverTest extends TestCase
{
    private Connection|MockObject $connection;

    protected function setUp(): void
    {
        $this->connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @throws Throwable
     */
    public function test_create_table_and_indexes(): void
    {
        $expected_table = <<<SQL
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
        SQL;

        $this->connection
            ->expects($this->exactly(3))
            ->method('exec')
            ->withConsecutive(
                [ $expected_table ],
                [ "CREATE UNIQUE INDEX `country_week_product` ON `menus` (`country`, `week`, `product`)" ],
                [ "CREATE INDEX `my_week_index` ON `menus` (`week`)" ],
            );

        $schema = (new Schema([
            'id' => SchemaColumn::serial(primary: true),
            'uuid' => SchemaColumn::char(size: 36, unique: true),
            'country' => SchemaColumn::char(size: 2),
            'week' => SchemaColumn::char(size: 8),
            'product' => SchemaColumn::varchar(),
            'name' => SchemaColumn::varchar(),
            'created_at' => SchemaColumn::timestamp(default: SchemaColumn::CURRENT_TIMESTAMP),
        ]))
            ->index([ 'country', 'week', 'product' ], unique: true)
            ->index('week', name: "my_week_index");

        $table_name = "menus";

        $this->makeDriver()->create_table($table_name, $schema);
        $this->makeDriver()->create_indexes($table_name, $schema);
    }

    private function makeDriver(): SQLiteDriver
    {
        return new SQLiteDriver(fn() => $this->connection);
    }
}
