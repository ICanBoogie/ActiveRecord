<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelCollection;
use ICanBoogie\ActiveRecord\ModelNotDefined;
use ICanBoogie\ActiveRecord\ModelProvider;
use ICanBoogie\ActiveRecord\RecordNotValid;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecordTest\Sample;
use ICanBoogie\ActiveRecordTest\ValidateCase;

/**
 * @group record
 */
class ActiveRecordTest extends \PHPUnit\Framework\TestCase
{
	private $sample_model;

	public function setUp()
	{
		$sample_model = &$this->sample_model;

		if ($sample_model)
		{
			return;
		}

		$sample_model = $this->mockModel();

		ModelProvider::define(function($model_id) use ($sample_model) {

			if ($model_id === 'sample')
			{
				return $sample_model;
			}

			return null;

		});
	}

	public function test_should_resolve_model_id_from_const()
	{
		$record = new Sample;
		$this->assertSame($record->model_id, Sample::MODEL_ID);
	}

	public function test_should_resolve_model_from_const()
	{
		$record = new Sample;
		$this->assertSame($this->sample_model, $record->model);
	}

	public function test_should_use_provided_model()
	{
		$model = $this->mockModel();
		$record = new Sample($model);
		$this->assertSame($model, $record->model);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_should_throw_exception_on_invalid_model()
	{
		new Sample(123);
	}

	public function test_sleep_should_remove_model()
	{
		$model = $this->mockModel();
		$record = new Sample($model);
		$array = $record->__sleep();

		$this->assertArrayNotHasKey('model', $array);
	}

	public function test_sleep_should_remove_any_instance_of_self()
	{
		$model = $this->mockModel();
		$property = 'p' . uniqid();
		$record = new Sample($model);
		$record->$property = new Sample($model);

		$array = $record->__sleep();

		$this->assertArrayNotHasKey($property, $array);
	}

	public function test_serialize_should_preserve_model_id()
	{
		$record = new ActiveRecord($this->sample_model);
		$serialized_record = serialize($record);
		$unserialized_record = unserialize($serialized_record);

		$this->assertEquals($record->model_id, $unserialized_record->model_id);

		$record = new Sample($this->sample_model);
		$serialized_record = serialize($record);
		$unserialized_record = unserialize($serialized_record);
		$this->assertEquals($record->model_id, $unserialized_record->model_id);
	}

	public function test_debug_info_should_exclude_model()
	{
		$model = $this->mockModel();
		$property = 'p' . uniqid();
		$record = new Sample($model);
		$record->$property = uniqid();

		$array = $record->__debugInfo();
		$this->assertArrayNotHasKey("\0" . ActiveRecord::class . "\0model", $array);
		$this->assertArrayHasKey($property, $array);
	}

	public function test_save()
	{
		$id = mt_rand(10000, 100000);
		$reverse = uniqid();
		$primary = 'id';
		$allow_null_with_value = uniqid();

		$schema = new Schema([

			$primary => 'serial',
			'reversed' => 'varchar',
			'date' => 'datetime',
			'do_not_allow_null' => [ 'varchar' ],
			'allow_null' => [ 'varchar', 'null' => true ],
			'allow_null_with_value' => [ 'varchar', 'null' => true ]

		]);

		$model = $this
			->getMockBuilder(Model::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_extended_schema', 'get_primary', 'save' ])
			->getMock();
		$model
			->expects($this->once())
			->method('get_primary')
			->willReturn($primary);
		$model
			->expects($this->once())
			->method('get_extended_schema')
			->willReturn($schema);
		$model
			->expects($this->once())
			->method('save')
			->with([

				'reverse' => strrev($reverse),
				'allow_null' => null,
				'allow_null_with_value' => $allow_null_with_value

			])
			->willReturn($id);

		$record = new Sample($model);
		$record->reverse = $reverse;
		$record->{ 'do_not_allow_null' } = null;
		$record->{ 'allow_null' } = null;
		$record->{ 'allow_null_with_value' } = $allow_null_with_value;

		$this->assertSame($id, $record->save());
	}

	/**
	 * @expectedException \LogicException
	 */
	public function test_delete_missing_primary()
	{
		$model = $this->mockModel();
		$record = new ActiveRecord($model);
		$record->delete();
	}

	/**
	 * @return Model
	 */
	private function mockModel()
	{
		return $this
			->getMockBuilder(Model::class)
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @group validate
	 */
	public function test_validate()
	{
		$model = $this
			->getMockBuilder(Model::class)
			->disableOriginalConstructor()
			->getMock();

		$record = new ValidateCase($model);

		try
		{
			$record->save();
		}
		catch (RecordNotValid $e)
		{
			$errors = $e->errors;

			$this->assertArrayNotHasKey('id', $errors);
			$this->assertArrayHasKey('name', $errors);
			$this->assertArrayHasKey('email', $errors);
			$this->assertArrayHasKey('timezone', $errors);

			return;
		}

		$this->fail("Expected RecordNotValid");
	}
}
