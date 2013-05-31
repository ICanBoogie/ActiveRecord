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
		(
			array
			(
				Model::NAME => 'animals',
				Model::CONNECTION => self::$connection,
				Model::SCHEMA => array
				(
					'fields' => array
					(
						'id' => 'serial',
						'name' => 'varchar',
						'date' => 'timestamp',
						'legs' => 'integer'
					)
				)
			)
		);

		self::$dogs = new Model
		(
			array
			(
				Model::ACTIVERECORD_CLASS => 'ICanBoogie\ActiveRecord\QueryTest\Dog',
				Model::EXTENDING => self::$animals,
				Model::NAME => 'dogs',
				Model::SCHEMA => array
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

		for ($i = 0 ; $i < self::$n ; $i++)
		{
			$properties = array
			(
				'name' => uniqid('', true),
				'date' => gmdate('Y-m-d H:i:s', time() + 60 * rand(1, 3600)),
				'legs' => rand(2, 16),
				'bark_volume' => rand(100, 1000) / 100
			);

			$key = self::$dogs->save($properties);

			self::$source[$key] = $properties;
		}
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

		$q = $m->order('id', array(1, 2, 3));
		$this->assertEquals("SELECT * FROM `animals` `animal` ORDER BY FIELD(id, '1', '2', '3')", (string) $q);

		$q = $m->order('id', 1, 2, 3);
		$this->assertEquals("SELECT * FROM `animals` `animal` ORDER BY FIELD(id, '1', '2', '3')", (string) $q);
	}
}

namespace ICanBoogie\ActiveRecord\QueryTest;

class Dog extends \ICanBoogie\ActiveRecord
{

}