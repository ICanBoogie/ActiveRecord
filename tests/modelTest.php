<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Tests\Model;

use ICanBoogie\ActiveRecord\Connection;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\RecordNotFound;

const CHARSET = 'utf-8';

class A extends Model
{
	protected function scope_ordered(Query $query, $direction='desc')
	{
		return $query->order('date ' . ($direction == 'desc' ? 'DESC' : 'ASC'));
	}
}

class ModelTest extends \PHPUnit_Framework_TestCase
{
	private $connection;
	private $model;

	public function setUp()
	{
		$connection = new Connection('sqlite::memory:');

		$model = new A
		(
			array
			(
				Model::T_NAME => 'test',
				Model::T_CONNECTION => $connection,
				Model::T_SCHEMA => array
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
		$u = uniqid();

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
		$u = uniqid();
		$a = array(1 => true, $u => false, 3 => true);

		$this->assertEquals($a, $m->exists(1, $u, 3));
		$this->assertEquals($a, $m->exists(array(1, $u, 3)));
	}

	public function testExistsConditionTrue()
	{
		$this->assertTrue($this->model->filter_by_name('Madonna')->exists);
	}

	public function testExistsConditionFalse()
	{
		$this->assertFalse($this->model->filter_by_name('Madonna ' . uniqid())->exists);
	}

	public function testCache()
	{
		$a = $this->model[1];
		$b = $this->model[1];

		$this->assertEquals(spl_object_hash($a), spl_object_hash($b));
	}
}