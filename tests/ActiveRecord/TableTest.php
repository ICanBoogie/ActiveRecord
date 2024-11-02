<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use ICanBoogie\ActiveRecord\Config\TableDefinition;
use ICanBoogie\ActiveRecord\Connection;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use ICanBoogie\ActiveRecord\StatementNotValid;
use ICanBoogie\ActiveRecord\Table;
use LogicException;
use PHPUnit\Framework\TestCase;

final class TableTest extends TestCase
{
    private Connection $connection;
    private Table $animals;
    private Schema $animals_schema;
    private Table $dogs;
    private Table $multi_column;

    protected function setUp(): void
    {
        $this->connection = $connection = new Connection(
            new ConnectionDefinition(
                id: Config::DEFAULT_CONNECTION_ID,
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

        $this->multi_column = new Table(
            $connection,
            new TableDefinition(
                name: 'multi_column',
                schema: (new SchemaBuilder())
                    ->add_integer('pk_1', primary: true)
                    ->add_integer('pk_2', primary: true)
                    ->add_character('title')
                    ->build()
            )
        );

        $this->animals->install();
        $this->dogs->install();
        $this->multi_column->install();
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

        $this->assertSame(" INNER JOIN `prefix_animals` `animal` USING(`id`)", $table->update_join);
    }

    public function test_get_select_join(): void
    {
        $table = $this->dogs;

        $this->assertSame("`dog` INNER JOIN `prefix_animals` `animal` USING(`id`)", $table->select_join);
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

    public function test_drop(): void
    {
        $this->assertTrue($this->animals->is_installed());

        $this->animals->drop();
        $this->assertFalse($this->animals->is_installed());

        // Should be fine with the `if_exists` option.
        $this->animals->drop(if_exists: true);

        // Should fail because the table doesn't exist.
        $this->expectException(StatementNotValid::class);
        $this->animals->drop();
    }

    //
    // Multi-column tests
    //

    public function test_insert_fails_when_values_is_empty(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("No values to insert");
        $this->multi_column->insert([]); // @phpstan-ignore-line
    }

    public function test_insert_multi_column(): void
    {
        $values = [ 'pk_1' => 1, 'pk_2' => 1, 'title' => "One" ];
        $this->multi_column->insert($values);
        $actual = $this->multi_column
            ->execute("SELECT * FROM {self} WHERE pk_1 = 1 AND pk_2 = 1")
            ->as_assoc
            ->one;
        $this->assertEquals($values, $actual);

        // should fail because of unique constraint
        $this->expectException(StatementNotValid::class);
        $this->multi_column->insert($values);
    }

    public function test_inserting_existing_multi_column_fails(): void
    {
        $values = [ 'pk_1' => 1, 'pk_2' => 1, 'title' => "One" ];
        $this->multi_column->insert($values);

        // should fail because of unique constraint
        $this->expectException(StatementNotValid::class);
        $this->multi_column->insert($values);
    }

    public function test_inserting_ignore_existing_multi_column(): void
    {
        $values = [ 'pk_1' => 1, 'pk_2' => 1, 'title' => "One" ];
        $this->multi_column->insert($values);
        $updated_values = [ 'title' => "One Updated" ] + $values;
        $this->multi_column->insert($updated_values, ignore: true);
        $actual = $this->multi_column
            ->execute("SELECT * FROM {self} WHERE pk_1 = 1 AND pk_2 = 1")
            ->as_assoc
            ->one;
        $this->assertEquals($values, $actual);
    }

    public function test_upsert_multi_column(): void
    {
        $values = [ 'pk_1' => 1, 'pk_2' => 1, 'title' => "One" ];
        $this->multi_column->insert($values);
        $updated_values = [ 'title' => "One Updated" ] + $values;
        $this->multi_column->insert($updated_values, upsert: true);

        $actual = $this->multi_column
            ->execute("SELECT * FROM {self} WHERE pk_1 = 1 AND pk_2 = 1")
            ->as_assoc
            ->one;
        $this->assertEquals($updated_values, $actual);
    }
}
