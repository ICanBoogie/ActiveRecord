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

class TableTest extends \PHPUnit_Framework_TestCase
{
	static private $connection;
	static private $animals;
	static private $dogs;

	static public function setUpBeforeClass()
	{
		self::$connection = new Connection
		(
			'sqlite::memory:', null, null, array
			(
				Connection::TABLE_NAME_PREFIX => 'prefix'
			)
		);

		self::$animals = new Table
		(
			array
			(
				Table::NAME => 'animals',
				Table::CONNECTION => self::$connection,
				Table::SCHEMA => array
				(
					'fields' => array
					(
						'id' => 'serial',
						'name' => 'varchar',
						'date' => 'timestamp'
					)
				)
			)
		);

		self::$dogs = new Table
		(
			array
			(
				Table::EXTENDING => self::$animals,
				Table::NAME => 'dogs',
				Table::SCHEMA => array
				(
					'fields' => array
					(
						'bark_volume' => 'float'
					)
				)
			)
		);

		self::$animals->install();
		self::$dogs->install();
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_invalid_table_name()
	{
		new Table(array(

			Table::NAME => 'invalid-name',
			Table::CONNECTION => self::$connection

		));
	}

	/*
	 * getters and setters
	 */

	/**
	 * @dataProvider provide_test_readonly_properties
	 * @expectedException ICanBoogie\PropertyNotWritable
	 *
	 * @param string $property Property name.
	 */
	public function test_readonly_properties($property)
	{
		self::$animals->$property = null;
	}

	public function provide_test_readonly_properties()
	{
		$properties = 'connection name name_unprefixed primary alias parent schema';

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

	public function test_get_name_unprefixed()
	{
		$this->assertEquals('animals', self::$animals->name_unprefixed);
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
		$this->assertInternalType('array', self::$animals->schema);
	}

	// TODO-20130303: schema

	public function test_get_parent()
	{
		$this->assertEquals(self::$animals, self::$dogs->parent);
	}

	public function test_extended_schema()
	{
		$schema = self::$dogs->extended_schema;
		$this->assertArrayHasKey('fields', $schema);
		$this->assertArrayHasKey('id', $schema['fields']);
		$this->assertArrayHasKey('name', $schema['fields']);
		$this->assertArrayHasKey('bark_volume', $schema['fields']);
	}

	public function test_resolve_statement__multi_column_primary_key()
	{
		$table = new Table(array(

			Table::CONNECTION => self::$connection,
			Table::NAME => 'testing',
			Table::SCHEMA => array
			(
				'fields' => array
				(
					'p1' => array('integer', 'big', 'primary' => true),
					'p2' => array('integer', 'big', 'primary' => true),
					'f1' => 'varchar'
				)
			)

		));

		$statement = 'SELECT FROM {self} WHERE {primary} = 1';

		$this->assertEquals('SELECT FROM prefix_testing WHERE __multicolumn_primary__p1_p2 = 1', $table->resolve_statement($statement));
	}
}