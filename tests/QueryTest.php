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

	public function test_join_with_query()
	{
		$subscribers = new Model([

			Model::CONNECTION => self::$connection,
			Model::NAME => 'subscribers',
			Model::SCHEMA => [

				'fields' => [

					'subscriber_id' => 'serial',
					'email' => 'varchar'

				]

			]

		]);

		$updates = new Model([

			Model::CONNECTION => self::$connection,
			Model::NAME => 'updates',
			Model::SCHEMA => [

				'fields' => [

					'update_id' => 'serial',
					'subscriber_id' => 'foreign',
					'updated_at' => 'datetime',
					'update_hash' => [ 'char', 40 ]

				]

			]

		]);

		$update_query = $updates->select('subscriber_id, updated_at, update_hash')
		->order('updated_at DESC');

		$subscriber_query = $subscribers
		->join($update_query, [ 'on' => 'subscriber_id' ])
		->group("`{alias}`.subscriber_id");

		$this->assertEquals("SELECT * FROM `subscribers` `subscriber` INNER JOIN(SELECT subscriber_id, updated_at, update_hash FROM `updates` `update` ORDER BY updated_at DESC) `update` USING(`subscriber_id`) GROUP BY `subscriber`.subscriber_id", (string) $subscriber_query);
	}

	public function test_join_with_model()
	{
		$subscribers = new Model([

			Model::CONNECTION => self::$connection,
			Model::NAME => 'subscribers',
			Model::SCHEMA => [

				'fields' => [

					'subscriber_id' => 'serial',
					'email' => 'varchar'

				]

			]

		]);

		$updates = new Model([

			Model::CONNECTION => self::$connection,
			Model::NAME => 'updates',
			Model::SCHEMA => [

				'fields' => [

					'update_id' => 'serial',
					'subscriber_id' => 'foreign',
					'updated_at' => 'datetime',
					'update_hash' => [ 'char', 40 ]

				]

			]

		]);

		$this->assertEquals("SELECT update_id, email FROM `updates` `update` INNER JOIN `subscribers` AS `subscriber` USING(`subscriber_id`)"
		, (string) $updates->select('update_id, email')->join($subscribers));

		$this->assertEquals("SELECT update_id, email FROM `updates` `update` INNER JOIN `subscribers` AS `sub` USING(`subscriber_id`)"
		, (string) $updates->select('update_id, email')->join($subscribers, [ 'alias' => 'sub' ]));

		$this->assertEquals("SELECT update_id, email FROM `updates` `update` LEFT JOIN `subscribers` AS `sub` USING(`subscriber_id`)"
		, (string) $updates->select('update_id, email')->join($subscribers, [ 'alias' => 'sub', 'mode' => 'LEFT' ]));
	}
}

namespace ICanBoogie\ActiveRecord\QueryTest;

class Dog extends \ICanBoogie\ActiveRecord
{

}