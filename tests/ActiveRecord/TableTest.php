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
use PHPUnit\Framework\Attributes\Group;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;

#[Group("db")]
final class TableTest extends DbTestCase
{
    private Connection $connection;
    private Table $animals;
    private Schema $animals_schema;
    private Table $dogs;
    private Table $multi_column;
    private Table $key_only;

    protected function setUp(): void
    {
        $this->connection = $connection = new Connection(
            new ConnectionDefinition(
                id: Config::DEFAULT_CONNECTION_ID,
                dsn: Fixtures::dsn(),
                username: Fixtures::username(),
                password: Fixtures::password(),
                table_name_prefix: 'prefix'
            )
        );

        $this->animals = new Table(
            $connection,
            new TableDefinition(
                name: 'animals',
                schema: $this->animals_schema = new SchemaBuilder()
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
                schema: new SchemaBuilder()
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
                schema: new SchemaBuilder()
                    ->add_integer('pk_1', primary: true)
                    ->add_integer('pk_2', primary: true)
                    ->add_character('title')
                    ->build()
            )
        );

        $this->key_only = new Table(
            $connection,
            new TableDefinition(
                name: 'key_only',
                schema: new SchemaBuilder()
                    ->add_integer('pk_1', primary: true)
                    ->add_integer('pk_2', primary: true)
                    ->build()
            )
        );

        $this->animals->install();
        $this->dogs->install();
        $this->multi_column->install();
        $this->key_only->install();
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
        $quote = $this->connection->quote_identifier(...);

        $this->assertSame(
            ' INNER JOIN ' . $quote('prefix_animals') . ' ' . $quote('animal') . ' USING(' . $quote('id') . ')',
            $table->update_join
        );
    }

    public function test_get_select_join(): void
    {
        $table = $this->dogs;
        $quote = $this->connection->quote_identifier(...);

        $this->assertSame(
            $quote('dog') . ' INNER JOIN ' . $quote('prefix_animals') . ' ' . $quote('animal') . ' USING(' . $quote('id') . ')',
            $table->select_join
        );
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
    // Save
    //

    public function test_save_inserts_then_updates(): void
    {
        $id = $this->animals->save([ 'name' => "Rex", 'date' => '2020-01-01 00:00:00' ]);

        $this->assertGreaterThan(0, $id);

        $this->assertSame($id, $this->animals->save([ 'name' => "Rex 2" ], $id));

        $actual = $this->animals
            ->execute("SELECT name, date FROM {self} WHERE id = ?", [ $id ])
            ->as_assoc
            ->all;

        $this->assertEquals([ [ 'name' => "Rex 2", 'date' => '2020-01-01 00:00:00' ] ], $actual);
    }

    public function test_save_discards_unknown_values(): void
    {
        $id = $this->animals->save([ 'name' => "Rex", 'date' => '2020-01-01 00:00:00', 'unknown' => 1 ]);

        $this->assertGreaterThan(0, $id);
    }

    public function test_save_spreads_values_over_the_hierarchy(): void
    {
        $id = $this->dogs->save([ 'name' => "Rex", 'date' => '2020-01-01 00:00:00', 'bark_volume' => 1.5 ]);
        $other_id = $this->dogs->save([ 'name' => "Max", 'date' => '2020-01-01 00:00:00', 'bark_volume' => 2.5 ]);

        $this->assertNotSame($id, $other_id);

        $animal = $this->animals->execute("SELECT name FROM {self} WHERE id = ?", [ $id ])->as_assoc->one;
        $dog = $this->dogs->execute("SELECT bark_volume FROM {self} WHERE id = ?", [ $id ])->as_assoc->one;

        $this->assertEquals([ 'name' => "Rex" ], $animal);
        $this->assertEquals([ 'bark_volume' => 1.5 ], $dog);
    }

    public function test_save_updates_the_hierarchy(): void
    {
        $id = $this->dogs->save([ 'name' => "Rex", 'date' => '2020-01-01 00:00:00', 'bark_volume' => 1.5 ]);

        $this->dogs->save([ 'name' => "Rex 2", 'bark_volume' => 2.5 ], $id);

        $animal = $this->animals->execute("SELECT name FROM {self} WHERE id = ?", [ $id ])->as_assoc->one;
        $dog = $this->dogs->execute("SELECT bark_volume FROM {self} WHERE id = ?", [ $id ])->as_assoc->one;

        $this->assertEquals([ 'name' => "Rex 2" ], $animal);
        $this->assertEquals([ 'bark_volume' => 2.5 ], $dog);
    }

    public function test_save_rolls_back_the_hierarchy_on_failure(): void
    {
        try {
            // `bark_volume` is required, so the insert into `dogs` fails after the one into `animals`.
            $this->dogs->save([ 'name' => "Rex", 'date' => '2020-01-01 00:00:00' ]);
            $this->fail("Expected StatementNotValid");
        } catch (StatementNotValid) {
            // expected
        }

        $this->assertFalse($this->connection->pdo->inTransaction());
        $this->assertSame(0, (int) $this->animals->execute("SELECT COUNT(*) FROM {self}")->rc);
    }

    public function test_save_joins_an_active_transaction(): void
    {
        $this->connection->begin();

        $this->dogs->save([ 'name' => "Rex", 'date' => '2020-01-01 00:00:00', 'bark_volume' => 1.5 ]);

        $this->assertTrue($this->connection->pdo->inTransaction(), "the transaction is not committed");

        $this->connection->pdo->rollBack();

        $this->assertSame(0, (int) $this->animals->execute("SELECT COUNT(*) FROM {self}")->rc);
    }

    public function test_insert_fails_when_ignore_and_upsert(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("`ignore` and `upsert` are mutually exclusive");

        $this->multi_column->insert([ 'pk_1' => 1, 'pk_2' => 1, 'title' => "One" ], ignore: true, upsert: true);
    }

    public function test_upsert_fails_without_primary_key(): void
    {
        $table = new Table(
            $this->connection,
            new TableDefinition(
                name: 'no_primary',
                schema: new SchemaBuilder()
                    ->add_character('title')
                    ->build()
            )
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("doesn't have a primary key");

        $table->insert([ 'title' => "One" ], upsert: true);
    }

    public function test_upsert_keeps_values_not_provided(): void
    {
        $table = new Table(
            $this->connection,
            new TableDefinition(
                name: 'ratings',
                schema: new SchemaBuilder()
                    ->add_integer('id', primary: true)
                    ->add_character('title')
                    ->add_integer('rating', null: true)
                    ->build()
            )
        );

        $table->install();
        $table->insert([ 'id' => 1, 'title' => "One", 'rating' => 5 ]);
        $table->insert([ 'id' => 1, 'title' => "One Updated" ], upsert: true);

        $actual = $table->execute("SELECT * FROM {self}")->as_assoc->all;

        $this->assertEquals([ [ 'id' => 1, 'title' => "One Updated", 'rating' => 5 ] ], $actual);
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

    //
    // Key-only tests: every column is part of the primary key, so an upsert has nothing to update.
    //

    public function test_upsert_key_only_inserts_new_row(): void
    {
        $values = [ 'pk_1' => 1, 'pk_2' => 1 ];
        $this->key_only->insert($values, upsert: true);

        $actual = $this->key_only
            ->execute("SELECT * FROM {self}")
            ->as_assoc
            ->all;
        $this->assertEquals([ $values ], $actual);
    }

    public function test_upsert_key_only_keeps_existing_row(): void
    {
        $values = [ 'pk_1' => 1, 'pk_2' => 1 ];
        $this->key_only->insert($values);
        $this->key_only->insert($values, upsert: true);

        $actual = $this->key_only
            ->execute("SELECT * FROM {self}")
            ->as_assoc
            ->all;
        $this->assertEquals([ $values ], $actual);
    }
}
