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

class TableTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @var Connection
	 */
	static private $connection;

	/**
	 * @var Table
	 */
	static private $animals;

	/**
	 * @var array
	 */
	static private $animals_schema_options;

	/**
	 * @var Table
	 */
	static private $dogs;

	static public function setUpBeforeClass(): void
	{
		self::$connection = new Connection
		(
			'sqlite::memory:', null, null, [

				ConnectionOptions::TABLE_NAME_PREFIX => 'prefix'
			]
		);

		self::$animals = new Table
		([
			Table::NAME => 'animals',
			Table::CONNECTION => self::$connection,
			Table::SCHEMA => self::$animals_schema_options = [

				'id' => 'serial',
				'name' => 'varchar',
				'date' => 'timestamp'

			]
		]);

		self::$dogs = new Table
		([
			Table::EXTENDING => self::$animals,
			Table::NAME => 'dogs',
			Table::SCHEMA => [

				'bark_volume' => 'float'

			]
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

		return array_map(function($v) { return (array) $v; }, explode(' ', $properties));
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
		$this->assertEquals(self::$animals_schema_options, self::$animals->schema_options);
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
		$this->assertInstanceOf(SchemaColumn::class, $schema['id']);
		$this->assertInstanceOf(SchemaColumn::class, $schema['name']);
		$this->assertInstanceOf(SchemaColumn::class, $schema['bark_volume']);
	}

	public function test_resolve_statement__multi_column_primary_key()
	{
		$table = new Table([

			Table::CONNECTION => self::$connection,
			Table::NAME => 'testing',
			Table::SCHEMA => [

				'p1' => [ 'integer', 'big', 'primary' => true ],
				'p2' => [ 'integer', 'big', 'primary' => true ],
				'f1' => 'varchar'

			]
		]);

		$statement = 'SELECT FROM {self} WHERE {primary} = 1';

		$this->assertEquals('SELECT FROM prefix_testing WHERE __multicolumn_primary__p1_p2 = 1', $table->resolve_statement($statement));
	}
}
