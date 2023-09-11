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
use ICanBoogie\ActiveRecord\Config\TableDefinition;
use ICanBoogie\ActiveRecord\Connection;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use ICanBoogie\ActiveRecord\Table;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class TableTest extends TestCase
{
    private Connection $connection;
    private Table $animals;
    private Schema $animals_schema;
    private Table $dogs;

    protected function setUp(): void
    {
        $this->connection = $connection = new Connection(
            new ConnectionDefinition(
                id: '',
                dsn: 'sqlite::memory:',
                table_name_prefix: 'prefix'
            )
        );

        $this->animals = new Table(
            $connection,
            new TableDefinition(
                name: 'animals',
                schema: $this->animals_schema = (new SchemaBuilder())
                    ->add_serial('id', primary: true)
                    ->add_character('name')
                    ->add_timestamp('date')
                    ->build()
            )
        );

        $this->dogs = new Table(
            $connection,
            new TableDefinition(
                name: 'dogs',
                schema: (new SchemaBuilder())
                    ->add_foreign('id', primary: true)
                    ->add_float('bark_volume')
                    ->build()
            ),
            $this->animals
        );

        $this->animals->install();
        $this->dogs->install();
    }

    /*
     * getters and setters
     */

    public function test_get_connection(): void
    {
        $this->assertEquals($this->connection, $this->animals->connection);
    }

    public function test_get_name(): void
    {
        $this->assertEquals('prefix_animals', $this->animals->name);
    }

    public function test_get_unprefixed_name(): void
    {
        $this->assertEquals('animals', $this->animals->unprefixed_name);
    }

    public function test_get_primary(): void
    {
        $this->assertEquals('id', $this->animals->primary);
    }

    public function test_get_inherited_primary(): void
    {
        $this->assertEquals('id', $this->dogs->primary);
    }

    public function test_get_alias(): void
    {
        $this->assertEquals('animal', $this->animals->alias);
        $this->assertEquals('dog', $this->dogs->alias);
    }

    public function test_get_schema(): void
    {
        $this->assertInstanceOf(Schema::class, $this->animals->schema);
    }

    public function test_get_schema_options(): void
    {
        $this->assertEquals($this->animals_schema, $this->animals->schema);
    }

    public function test_get_parent(): void
    {
        $this->assertEquals($this->animals, $this->dogs->parent);
    }

    public function test_get_update_join(): void
    {
        $table = $this->dogs;
        $method = new ReflectionMethod(Table::class, 'lazy_get_update_join');
        $method->setAccessible(true);

        $this->assertSame(" INNER JOIN `prefix_animals` `animal` USING(`id`)", $method->invoke($table));
    }

    public function test_get_select_join(): void
    {
        $table = $this->dogs;
        $method = new ReflectionMethod(Table::class, 'lazy_get_select_join');
        $method->setAccessible(true);

        $this->assertSame("`dog` INNER JOIN `prefix_animals` `animal` USING(`id`)", $method->invoke($table));
    }

    public function test_extended_schema(): void
    {
        $schema = $this->dogs->extended_schema;

        $this->assertInstanceOf(Schema::class, $schema);
    }

    public function test_resolve_statement__multi_column_primary_key(): void
    {
        $table = new Table(
            $this->connection,
            new TableDefinition(
                name: 'testing',
                schema: (new SchemaBuilder())
                    ->add_integer('p1', size: Schema\Integer::SIZE_BIG, primary: true)
                    ->add_integer('p2', size: Schema\Integer::SIZE_BIG, primary: true)
                    ->add_character('f1')
                    ->build()
            )
        );

        $statement = 'SELECT * FROM {self} WHERE {primary} = 1';

        $this->assertEquals(
            'SELECT * FROM prefix_testing WHERE __multicolumn_primary__p1_p2 = 1',
            $table->resolve_statement($statement)
        );
    }
}
