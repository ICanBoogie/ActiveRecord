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

use ICanBoogie\DateTime;
use ICanBoogie\ActiveRecord\QueryTest\Dog;

class QueryTest extends \PHPUnit_Framework_TestCase
{
	static private $n = 10;
	static private $connection;
	static private $animals;
	static private $dogs;
	static private $source;

	static public function setUpBeforeClass()
	{
		self::$connection = new Connection('sqlite::memory:');

		self::$animals = new Model
		([
			Model::NAME => 'animals',
			Model::CONNECTION => self::$connection,
			Model::SCHEMA => [

				'fields' => [

					'id' => 'serial',
					'name' => 'varchar',
					'date' => 'timestamp',
					'legs' => 'integer'
				]
			]
		]);

		self::$dogs = new Model
		([
			Model::ACTIVERECORD_CLASS => 'ICanBoogie\ActiveRecord\QueryTest\Dog',
			Model::EXTENDING => self::$animals,
			Model::NAME => 'dogs',
			Model::SCHEMA => [

				'fields' => [

					'bark_volume' => 'float'
				]
			]
		]);

		self::$animals->install();
		self::$dogs->install();

		for ($i = 0 ; $i < self::$n ; $i++)
		{
			$properties = [

				'name' => uniqid('', true),
				'date' => gmdate('Y-m-d H:i:s', time() + 60 * rand(1, 3600)),
				'legs' => rand(2, 16),
				'bark_volume' => rand(100, 1000) / 100
			];

			$key = self::$dogs->save($properties);

			self::$source[$key] = $properties;
		}
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 * @dataProvider provide_test_readonly_properties
	 */
	public function test_readonly_properties($property)
	{
		$query = new Query(self::$animals);
		$query->$property = null;
	}

	public function provide_test_readonly_properties()
	{
		$properties = 'all conditions conditions_args count exists model model_scope one pairs prepared rc';
		return array_map(function($v) { return (array) $v; }, explode(' ', $properties));
	}

	public function test_one()
	{
		$this->assertInstanceOf('ICanBoogie\ActiveRecord\QueryTest\Dog', self::$dogs->one);
	}

	public function test_all()
	{
		$all = self::$dogs->all;

		$this->assertInternalType('array', $all);
		$this->assertEquals(self::$n, count($all));
	}

	public function test_order()
	{
		$m = self::$animals;

		$q = $m->order('name ASC, legs DESC');
		$this->assertEquals("SELECT * FROM `animals` `animal` ORDER BY name ASC, legs DESC", (string) $q);
	}

	public function test_order_by_field()
	{
		$m = self::$animals;

		$q = $m->order('id', [ 1, 2, 3 ]);
		$this->assertEquals("SELECT * FROM `animals` `animal` ORDER BY FIELD(id, '1', '2', '3')", (string) $q);

		$q = $m->order('id', 1, 2, 3);
		$this->assertEquals("SELECT * FROM `animals` `animal` ORDER BY FIELD(id, '1', '2', '3')", (string) $q);
	}

	public function test_conditions()
	{
		$query = new Query(self::$animals);

		$query->where([ 'name' => 'madonna' ])
		->filter_by_legs(2)
		->and('YEAR(date) = ?', 1958);

		$this->assertSame([

			"(`name` = ?)",
			"(`legs` = ?)",
			"(YEAR(date) = ?)"

		], $query->conditions);

		$this->assertSame([

			"madonna",
			2,
			1958

		], $query->conditions_args);
	}
}

namespace ICanBoogie\ActiveRecord\QueryTest;

class Dog extends \ICanBoogie\ActiveRecord
{

}