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

final class TableTest extends TestCase
{
    /**
     * @var Connection
     */
    private static $connection;

    /**
     * @var Table
     */
    private static $animals;

    private static Schema $animals_schema;

    /**
     * @var Table
     */
    private static $dogs;

    public static function setUpBeforeClass(): void
    {
        self::$connection = new Connection(
            'sqlite::memory:',
            null,
            null,
            [

                ConnectionOptions::TABLE_NAME_PREFIX => 'prefix'
            ]
        );

        self::$animals = new Table([
            Table::NAME => 'animals',
            Table::CONNECTION => self::$connection,
            Table::SCHEMA => self::$animals_schema = new Schema([
                'id' => SchemaColumn::serial(primary: true),
                'name' => SchemaColumn::varchar(),
                'date' => SchemaColumn::timestamp(),
            ])
        ]);

        self::$dogs = new Table([
            Table::EXTENDING => self::$animals,
            Table::NAME => 'dogs',
            Table::SCHEMA => new Schema([
                'bark_volume' => SchemaColumn::float(),
            ])
        ]);

        self::$animals->install();
        self::$dogs->install();
    }

    public function test_invalid_table_name()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Table([

            Table::NAME => 'invalid-name',
            Table::CONNECTION => self::$connection

        ]);
    }

    /*
     * getters and setters
     */

    /**
     * @dataProvider provide_test_readonly_properties
     */
    public function test_readonly_properties(string $property)
    {
        $this->expectException(\ICanBoogie\PropertyNotWritable::class);
        self::$animals->$property = null;
    }

    public function provide_test_readonly_properties()
    {
        $properties = 'connection name unprefixed_name primary alias parent schema';

        return array_map(function ($v) {
            return (array) $v;
        }, explode(' ', $properties));
    }

    public function test_get_connection()
    {
        $this->assertEquals(self::$connection, self::$animals->connection);
    }

    public function test_get_name()
    {
        $this->assertEquals('prefix_animals', self::$animals->name);
    }

    public function test_get_unprefixed_name()
    {
        $this->assertEquals('animals', self::$animals->unprefixed_name);
    }

    public function test_get_primary()
    {
        $this->assertEquals('id', self::$animals->primary);
    }

    public function test_get_inherited_primary()
    {
        $this->assertEquals('id', self::$dogs->primary);
    }

    public function test_get_alias()
    {
        $this->assertEquals('animal', self::$animals->alias);
        $this->assertEquals('dog', self::$dogs->alias);
    }

    public function test_get_schema()
    {
        $this->assertInstanceOf(Schema::class, self::$animals->schema);
    }

    public function test_get_schema_options()
    {
        $this->assertEquals(self::$animals_schema, self::$animals->schema);
    }

    public function test_get_parent()
    {
        $this->assertEquals(self::$animals, self::$dogs->parent);
    }

    public function test_get_update_join()
    {
        $table = self::$dogs;
        $method = new \ReflectionMethod(Table::class, 'lazy_get_update_join');
        $method->setAccessible(true);

        $this->assertSame(" INNER JOIN `prefix_animals` `animal` USING(`id`)", $method->invoke($table));
    }

    public function test_get_select_join()
    {
        $table = self::$dogs;
        $method = new \ReflectionMethod(Table::class, 'lazy_get_select_join');
        $method->setAccessible(true);

        $this->assertSame("`dog` INNER JOIN `prefix_animals` `animal` USING(`id`)", $method->invoke($table));
    }

    public function test_extended_schema()
    {
        $schema = self::$dogs->extended_schema;

        $this->assertInstanceOf(Schema::class, $schema);
    }

    public function test_resolve_statement__multi_column_primary_key(): void
    {
        $table = new Table([

            Table::CONNECTION => self::$connection,
            Table::NAME => 'testing',
            Table::SCHEMA => new Schema([
                'p1' => new SchemaColumn(type: 'int', size: 'big', primary: true),
                'p2' => new SchemaColumn(type: 'int', size: 'big', primary: true),
                'f1' => SchemaColumn::varchar(),
            ])
        ]);

        $statement = 'SELECT FROM {self} WHERE {primary} = 1';

        $this->assertEquals(
            'SELECT FROM prefix_testing WHERE __multicolumn_primary__p1_p2 = 1',
            $table->resolve_statement($statement)
        );
    }
}
