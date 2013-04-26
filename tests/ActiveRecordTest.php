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

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\ActiveRecordTest\Extended;
use ICanBoogie\DateTime;

class ActiveRecordTest extends \PHPUnit_Framework_TestCase
{
	static private $connection;
	static private $model;

	static public function setUpBeforeClass()
	{
		self::$connection = new Connection('sqlite::memory:');
		self::$model = new Model
		(
			array
			(
				Model::CONNECTION => self::$connection,
				Model::NAME => 'testing',
				Model::SCHEMA => array
				(
					'fields' => array
					(
						'id' => 'serial',
						'title' => 'varchar',
						'date' => 'datetime'
					)
				)
			)
		);

		self::$model->install();
	}

	public function test_construct()
	{
		new ActiveRecord(self::$model);
		new ActiveRecord('testing');
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_construct_invalid()
	{
		new ActiveRecord(new \stdClass());
	}

	public function test_get_model()
	{
		$record = new ActiveRecord(self::$model);
		$this->assertEquals(self::$model, $record->model);
	}

	public function test_deprecated_get_model()
	{
		$record = new ActiveRecord(self::$model);
		$this->assertEquals(self::$model, $record->_model);
	}

	/**
	 * @expectedException \ICanBoogie\PropertyNotWritable
	 */
	public function test_set_model()
	{
		$record = new ActiveRecord(self::$model);
		$record->model = null;
	}

	public function test_get_model_id()
	{
		$record = new ActiveRecord(self::$model);
		$this->assertEquals(self::$model->id, $record->model_id);
	}

	public function test_deprecated_get_model_id()
	{
		$record = new ActiveRecord(self::$model);
		$this->assertEquals(self::$model->id, $record->_model_id);
	}

	/**
	 * @expectedException \ICanBoogie\PropertyNotWritable
	 */
	public function test_set_model_id()
	{
		$record = new ActiveRecord(self::$model);
		$record->model_id = null;
	}

	public function test_sleep()
	{
		$record = new ActiveRecord(self::$model);
		$properties = $record->__sleep();

		$this->assertNotContains('model', $properties);
		$this->assertContains('model_id', $properties);
	}

	public function test_to_array()
	{
		$record = new ActiveRecord(self::$model);
		$array = $record->to_array();

		$this->assertNotContains('model', $array);
		$this->assertNotContains('model_id', $array);
	}

	public function test_serialize()
	{
		$record = new ActiveRecord(self::$model);
		$serialized_record = serialize($record);
		$unserialized_record = unserialize($serialized_record);
		$this->assertEquals($record->model_id, $unserialized_record->model_id);

		$record = new Extended(self::$model);
		$serialized_record = serialize($record);
		$unserialized_record = unserialize($serialized_record);
		$this->assertEquals($record->model_id, $unserialized_record->model_id);
	}

	public function test_datetime()
	{
		$record = new ActiveRecord(self::$model);
		$record->title = 'datetime';
		$record->date = '2013-03-06 18:30:30';

		$key = $record->save();
 		$record = self::$model[$key];
		$this->assertEquals('2013-03-06 18:30:30', $record->date);

		$date = new DateTime('2013-03-06 18:30:30', 'Europe/Paris');
		$record->date = $date;
		$record->save();

		$record = self::$model[$key];
		$this->assertEquals($record->date, $date->utc->as_db);

		$record = self::$model->where('date = ?', $date)->one;
		$this->assertInstanceOf('ICanBoogie\ActiveRecord', $record);
		$this->assertEquals($key, $record->id);
	}

	public function test_create_return_key()
	{
		$model = self::$model;
		$model->truncate();

		$a1 = new ActiveRecord($model);
		$a1->title = 'a1';
		$a1->date = '2013-03-06 18:30:30';

		$this->assertEquals(1, $a1->save());

		$a2 = new ActiveRecord($model);
		$a2->title = 'a2';
		$a2->date = '2013-03-06 18:30:30';

		$this->assertEquals(2, $a2->save());

		$a3 = new ActiveRecord($model);
		$a3->title = 'a3';
		$a3->date = '2013-03-06 18:30:30';
		$this->assertEquals(3, $a3->save());

		#

		$this->assertEquals(1, $a1->save());
	}

	public function test_delete()
	{
		$model = self::$model;
		$record = $model[1];
		$this->assertTrue($record->delete());

		$record = $model[2];
		$this->assertTrue($record->delete());

		$record = $model[3];
		$this->assertTrue($record->delete());
	}

	public function test_invalid_delete()
	{
		$record = new ActiveRecord(self::$model);
		$record->id = 999;
		$this->assertFalse($record->delete());
	}

	public function testPropertiesWithActiveRecordValueAreNotExportedBySleep()
	{
		$record = new ActiveRecord(self::$model);
		$record->int = 13;
		$record->text = "Text";
		$record->record = new ActiveRecord(self::$model);

		$properties = $record->__sleep();
		$this->assertArrayHasKey('int', $properties);
		$this->assertArrayHasKey('text', $properties);
		$this->assertArrayNotHasKey('record', $properties);
	}
}

namespace ICanBoogie\ActiveRecord\ActiveRecordTest;

class Extended extends \ICanBoogie\ActiveRecord
{

}