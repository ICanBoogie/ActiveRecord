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

use ICanBoogie\ActiveRecord\ModelTest\A;

class ModelTest extends \PHPUnit_Framework_TestCase
{
	private $connection;
	private $model;

	static public $query_connection;
	static public $query_model;

	static public function setUpBeforeClass()
	{
		self::$query_connection = new Connection('sqlite::memory:');
		self::$query_model = new Model
		(
			array
			(
				Model::NAME => 'nodes',
				Model::CONNECTION => self::$query_connection,
				Model::SCHEMA => array
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

		self::$query_model->install();
	}

	public function setUp()
	{
		$connection = new Connection('sqlite::memory:', null, null, array(Connection::TABLE_NAME_PREFIX => 'prefix'));

		$model = new A
		(
			array
			(
				Model::NAME => 'tests',
				Model::CONNECTION => $connection,
				Model::SCHEMA => array
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

		$model->install();

		$model->save(array('name' => 'Madonna', 'date' => '1958-08-16'));
		$model->save(array('name' => 'Lady Gaga', 'date' => '1986-03-28'));
		$model->save(array('name' => 'Cat Power', 'date' => '1972-01-21'));

		$this->connection = $connection;
		$this->model = $model;
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConnection()
	{
		$model = new Model(array
		(
			Model::NAME => 'tests',
			Model::CONNECTION => 'invalid_connection',
			Model::SCHEMA => array
			(
				'fields' => array
				(
					'id' => 'serial',
					'name' => 'varchar',
					'date' => 'timestamp'
				)
			)
		));
	}

	/*
	 * Setters/Getters
	 */

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testIdIsNotWritable()
	{
		self::$query_model->id = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testActiverecordClassIsNotWritable()
	{
		self::$query_model->activerecord_class = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testExistsIsNotWritable()
	{
		self::$query_model->exists = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testCountIsNotWritable()
	{
		self::$query_model->count = true;
	}

	/*
	 *
	 */

	public function testFind()
	{
		$this->assertInstanceOf('ICanBoogie\ActiveRecord', $this->model[1]);
	}

	/**
	 * @expectedException ICanBoogie\ActiveRecord\RecordNotFound
	 */
	public function testRecordNotFound()
	{
		$this->model[123456789];
	}

	/**
	 * @expectedException ICanBoogie\ActiveRecord\RecordNotFound
	 */
	public function testRecordNotFoundSet()
	{
		$this->model->find(array(123456780, 123456781, 123456782, 123456783));
	}

	public function testRecordNotFoundPartial()
	{
		try
		{
			$this->model->find(array(123456780, 1, 123456782, 123456783, 2));

			$this->fail("A RecordNotFound exception should have been raised");
		}
		catch (RecordNotFound $e)
		{
			$records = $e->records;

			$this->assertNull($records[123456780]);
			$this->assertNotNull($records[1]);
			$this->assertNull($records[123456782]);
			$this->assertNull($records[123456783]);
			$this->assertNotNull($records[2]);

			$this->assertInstanceOf('ICanBoogie\ActiveRecord', $records[1]);
			$this->assertInstanceOf('ICanBoogie\ActiveRecord', $records[2]);
		}
	}

	public function testScopeAsProperty()
	{
		$a = $this->model;

		try
		{
			$q = $a->ordered;
			$this->assertInstanceOf('ICanBoogie\ActiveRecord\Query', $q);
		}
		catch (\Exception $e)
		{
			$this->fail("An exception was raised: " . $e->getMessage());
		}

		$record = $q->one;
		$this->assertInstanceOf('ICanBoogie\ActiveRecord', $record);
		$this->assertEquals('Lady Gaga', $record->name);
	}

	public function testScopeAsMethod()
	{
		$a = $this->model;

		try
		{
			$q = $a->ordered('asc');
			$this->assertInstanceOf('ICanBoogie\ActiveRecord\Query', $q);
		}
		catch (\Exception $e)
		{
			$this->fail("An exception was raised: " . $e->getMessage());
		}

		$record = $q->one;
		$this->assertInstanceOf('ICanBoogie\ActiveRecord', $record);
		$this->assertEquals('Madonna', $record->name);
	}

	/**
	 * @expectedException ICanBoogie\ActiveRecord\ScopeNotDefined
	 */
	public function testScopeNotDefined()
	{
		$a = $this->model;
		$q = $a->ordered('asc');
		$q->undefined_scope();
	}

	/*
	 * Record existance
	 */

	/**
	 * `exists()` must return `true` when a record or all the records of a subset exist.
	 */
	public function testExistsTrue()
	{
		$m = $this->model;
		$this->assertTrue($m->exists(1));
		$this->assertTrue($m->exists(1, 2, 3));
		$this->assertTrue($m->exists(array(1, 2, 3)));
	}

	/**
	 * `exists()` must return `false` when a record or all the records of a subset don't exist.
	 */
	public function testExistsFalse()
	{
		$m = $this->model;
		$u = rand(999, 9999);

		$this->assertFalse($m->exists($u));
		$this->assertFalse($m->exists($u+1, $u+2, $u+3));
		$this->assertFalse($m->exists(array($u+1, $u+2, $u+3)));
	}

	/**
	 * `exists()` must return an array when some records of a subset don't exist.
	 */
	public function testExistsMixed()
	{
		$m = $this->model;
		$u = rand(999, 9999);
		$a = array(1 => true, $u => false, 3 => true);

		$this->assertEquals($a, $m->exists(1, $u, 3));
		$this->assertEquals($a, $m->exists(array(1, $u, 3)));
	}

	public function testExistsCondition()
	{
		$this->assertTrue($this->model->filter_by_name('Madonna')->exists);
		$this->assertFalse($this->model->filter_by_name('Madonna ' . uniqid())->exists);
	}

	public function testCache()
	{
		$a = $this->model[1];
		$b = $this->model[1];

		$this->assertEquals(spl_object_hash($a), spl_object_hash($b));
	}

	public function test_belongs_to()
	{
		$connection = new Connection('sqlite::memory:');

		$drivers = new Model
		(
			array
			(
				Model::ACTIVERECORD_CLASS => __NAMESPACE__ . '\ModelTest\Driver',
				Model::CONNECTION => $connection,
				Model::NAME => 'drivers',
				Model::SCHEMA => array
				(
					'fields' => array
					(
						'driver_id' => 'serial',
						'name' => 'varchar'
					)
				)
			)
		);

		$brands = new Model
		(
			array
			(
				Model::ACTIVERECORD_CLASS => __NAMESPACE__ . '\ModelTest\Brand',
				Model::CONNECTION => $connection,
				Model::NAME => 'brands',
				Model::SCHEMA => array
				(
					'fields' => array
					(
						'brand_id' => 'serial',
						'name' => 'varchar'
					)
				)
			)
		);

		$cars = new Model
		(
			array
			(
				Model::ACTIVERECORD_CLASS => __NAMESPACE__ . '\ModelTest\Car',
// 				Model::BELONGS_TO => array($drivers, $brands),
				Model::CONNECTION => $connection,
				Model::NAME => 'cars',
				Model::SCHEMA => array
				(
					'fields' => array
					(
						'car_id' => 'serial',
						'driver_id' => 'foreign',
						'brand_id' => 'foreign',
						'name' => 'varchar'
					)
				)
			)
		);

		$cars->belongs_to($drivers, $brands);

		$drivers->install();
		$brands->install();
		$cars->install();

		$car = $cars->new_record();
		$this->assertInstanceOf(__NAMESPACE__ . '\ModelTest\Car', $car);

		$car->name = '4two';
		$this->assertNull($car->driver);
		$this->assertNull($car->brand);

		# driver

		$driver = $drivers->new_record();
		$this->assertInstanceOf(__NAMESPACE__ . '\ModelTest\Driver', $driver);
		$driver->name = 'Madonna';
		$driver_id = $driver->save();

		# brand

		$brand = $brands->new_record();
		$this->assertInstanceOf(__NAMESPACE__ . '\ModelTest\Brand', $brand);
		$brand->name = 'Smart';
		$brand_id = $brand->save();

		$car->driver_id = $driver_id;
		$car->brand_id = $brand_id;
		$car->save();

		unset($car->driver);
		unset($car->brand);

		$this->assertInstanceof(__NAMESPACE__ . '\ModelTest\Driver', $car->driver);
		$this->assertInstanceof(__NAMESPACE__ . '\ModelTest\Brand', $car->brand);
	}

	public function testCacheRevokedOnSave()
	{
		$connection = new Connection('sqlite::memory:');

		$drivers = new Model
		(
			array
			(
				Model::ACTIVERECORD_CLASS => __NAMESPACE__ . '\ModelTest\Driver',
				Model::CONNECTION => $connection,
				Model::NAME => 'drivers_3',
				Model::SCHEMA => array
				(
					'fields' => array
					(
						'driver_id' => 'serial',
						'name' => 'varchar'
					)
				)
			)
		);

		$drivers->install();

		$driver_id = $drivers->save(array('name' => 'madonna'));
		$driver = $drivers[$driver_id];
		$drivers->save(array('name' => 'lady gaga'), $driver_id);
		$driver_now = $drivers[$driver_id];

		$this->assertEquals('madonna', $driver->name);
		$this->assertEquals('lady gaga', $driver_now->name);
		$this->assertNotEquals(spl_object_hash($driver), spl_object_hash($driver_now));
	}

	/*
	 * Querying
	 */

	public function testForwardSelect()
	{
		$m = self::$query_model;
		$this->assertEquals("SELECT nid, UPPER(name) FROM `nodes` `node`", (string) $m->select('nid, UPPER(name)'));
	}

	public function testForwardJoins()
	{
		$m = self::$query_model;
		$this->assertEquals("SELECT * FROM `nodes` `node` INNER JOIN other USING(nid)", (string) $m->joins('INNER JOIN other USING(nid)'));
	}

	public function testForwardWhere()
	{
		$m = self::$query_model;
		$this->assertEquals("SELECT * FROM `nodes` `node` WHERE (`nid` = ? AND `name` = ?)", (string) $m->where(array('nid' => 1, 'name' => 'madonna')));
	}

	public function testForwardGroup()
	{
		$m = self::$query_model;
		$this->assertEquals("SELECT * FROM `nodes` `node` GROUP BY name", (string) $m->group('name'));
	}

	public function testForwardOrder()
	{
		$m = self::$query_model;
		$this->assertEquals("SELECT * FROM `nodes` `node` ORDER BY nid", (string) $m->order('nid'));
		$this->assertEquals("SELECT * FROM `nodes` `node` ORDER BY FIELD(nid, '1', '2', '3')", (string) $m->order('nid', 1, 2, 3));
	}

	public function testForwardLimit()
	{
		$m = self::$query_model;
		$this->assertEquals("SELECT * FROM `nodes` `node` LIMIT 5", (string) $m->limit(5));
		$this->assertEquals("SELECT * FROM `nodes` `node` LIMIT 5, 10", (string) $m->limit(5, 10));
	}

	public function testForwardOffset()
	{
		$m = self::$query_model;
		$this->assertEquals("SELECT * FROM `nodes` `node` LIMIT 5, " . Query::LIMIT_MAX, (string) $m->offset(5));
	}
}

namespace ICanBoogie\ActiveRecord\ModelTest;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;

class A extends Model
{
	protected function scope_ordered(Query $query, $direction='desc')
	{
		return $query->order('date ' . ($direction == 'desc' ? 'DESC' : 'ASC'));
	}
}

class Driver extends ActiveRecord
{
	public $driver_id;
	public $name;
}

class Brand extends ActiveRecord
{
	public $brand_id;
	public $name;
}

class Car extends ActiveRecord
{
	public $car_id;
	public $driver_id = 0;
	public $brand_id = 0;
	public $name = '';
}