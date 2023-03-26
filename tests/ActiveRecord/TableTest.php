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
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaColumn;
use ICanBoogie\ActiveRecord\Table;
use ICanBoogie\ActiveRecord\TableDefinition;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class TableTest extends TestCase
{
    private static Connection $connection;
    private static Table $animals;
    private static Schema $animals_schema;
    private static Table $dogs;

    public static function setUpBeforeClass(): void
    {
        self::$connection = $connection = new Connection(
            new ConnectionDefinition(
                id: '',
                dsn: 'sqlite::memory:',
                table_name_prefix: 'prefix'
            )
        );

        self::$animals = new Table(
            $connection,
            new TableDefinition(
                name: 'animals',
                schema: self::$animals_schema = new Schema([
                    'id' => SchemaColumn::serial(primary: true),
                    'name' => SchemaColumn::varchar(),
                    'date' => SchemaColumn::timestamp(),
                ])
            )
        );

        self::$dogs = new Table(
            $connection,
            new TableDefinition(
                name: 'dogs',
                schema: new Schema([
                    'id' => SchemaColumn::foreign(primary: true),
                    'bark_volume' => SchemaColumn::float(),
                ])
            ),
            self::$animals
        );

        self::$animals->install();
        self::$dogs->install();
    }

    /*
     * getters and setters
     */

    public function test_get_connection(): void
    {
        $this->assertEquals(self::$connection, self::$animals->connection);
    }

    public function test_get_name(): void
    {
        $this->assertEquals('prefix_animals', self::$animals->name);
    }

    public function test_get_unprefixed_name(): void
    {
        $this->assertEquals('animals', self::$animals->unprefixed_name);
    }

    public function test_get_primary(): void
    {
        $this->assertEquals('id', self::$animals->primary);
    }

    public function test_get_inherited_primary(): void
    {
        $this->assertEquals('id', self::$dogs->primary);
    }

    public function test_get_alias(): void
    {
        $this->assertEquals('animal', self::$animals->alias);
        $this->assertEquals('dog', self::$dogs->alias);
    }

    public function test_get_schema(): void
    {
        $this->assertInstanceOf(Schema::class, self::$animals->schema);
    }

    public function test_get_schema_options(): void
    {
        $this->assertEquals(self::$animals_schema, self::$animals->schema);
    }

    public function test_get_parent(): void
    {
        $this->assertEquals(self::$animals, self::$dogs->parent);
    }

    public function test_get_update_join(): void
    {
        $table = self::$dogs;
        $method = new ReflectionMethod(Table::class, 'lazy_get_update_join');
        $method->setAccessible(true);

        $this->assertSame(" INNER JOIN `prefix_animals` `animal` USING(`id`)", $method->invoke($table));
    }

    public function test_get_select_join(): void
    {
        $table = self::$dogs;
        $method = new ReflectionMethod(Table::class, 'lazy_get_select_join');
        $method->setAccessible(true);

        $this->assertSame("`dog` INNER JOIN `prefix_animals` `animal` USING(`id`)", $method->invoke($table));
    }

    public function test_extended_schema(): void
    {
        $schema = self::$dogs->extended_schema;

        $this->assertInstanceOf(Schema::class, $schema);
    }

    public function test_resolve_statement__multi_column_primary_key(): void
    {
        $table = new Table(
            self::$connection,
            new TableDefinition(
                name: 'testing',
                schema: new Schema([
                    'p1' => new SchemaColumn(type: 'int', size: 'big', primary: true),
                    'p2' => new SchemaColumn(type: 'int', size: 'big', primary: true),
                    'f1' => SchemaColumn::varchar(),
                ])
            )
        );

        $statement = 'SELECT * FROM {self} WHERE {primary} = 1';

        $this->assertEquals(
            'SELECT * FROM prefix_testing WHERE __multicolumn_primary__p1_p2 = 1',
            $table->resolve_statement($statement)
        );
    }
}
