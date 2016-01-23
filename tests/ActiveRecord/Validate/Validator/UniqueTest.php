<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Validate\Validator;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Validate\Reader\RecordAdapter;
use ICanBoogie\Validate\Context;

/**
 * @group validate
 * @medium
 */
class UniqueTest extends \PHPUnit_Framework_TestCase
{
	public function test_normalize_options()
	{
		$column = uniqid();
		$validator = new Unique;
		$options = $validator->normalize_params([ Unique::OPTION_COLUMN => $column ]);
		$this->assertArrayHasKey(Unique::OPTION_COLUMN, $options);
		$this->assertArrayNotHasKey(0, $options);
		$this->assertSame($column, $options[Unique::OPTION_COLUMN]);
	}

	/**
	 * @dataProvider provide_test_validate
	 *
	 * @param string $column
	 * @param bool $should_use_primary
	 */
	public function test_validate($column, $should_use_primary)
	{
		$attribute = 'attribute' . uniqid();
		$value = 'value' . uniqid();
		$primary = 'primary' . uniqid();
		$key = $should_use_primary ? 'key' . uniqid() : null;
		$context = $this->mockContext($attribute, $value, $column ?: $attribute, $primary, $key);
		$context->validator_params = [ Unique::OPTION_COLUMN => $column ];

		$validator = new Unique;
		$validator->validate($value, $context);
	}

	/**
	 * @return array
	 */
	public function provide_test_validate()
	{
		return [

			[ null, false ],
			[ 'email', false ],
			[ null, true ],
			[ 'email', true ],

		];
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_validate_with_invalid_reader()
	{
		$context = new Context;
		$context->attribute = uniqid();
		$context->reader = new \stdClass;

		$validator = new Unique;
		$validator->validate(uniqid(), $context);
	}

	/**
	 * @param string $attribute
	 * @param string $value
	 * @param string $column
	 * @param string $primary
	 * @param string $key
	 *
	 * @return Context
	 */
	private function mockContext($attribute, $value, $column, $primary, $key)
	{
		$context = new Context;
		$context->reader = $this->mockReader($value, $column, $primary, $key);
		$context->attribute = $attribute;

		return $context;
	}

	/**
	 * @param string $value
	 * @param string $column
	 * @param string $primary
	 * @param string $key
	 *
	 * @return RecordAdapter
	 */
	private function mockReader($value, $column, $primary, $key)
	{
		$expected = true;

		$query = $this
			->getMockBuilder(ActiveRecord\Query::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_exists' ])
			->getMock();
		$query
			->expects($this->once())
			->method('get_exists')
			->willReturn($expected);

		$model = $this
			->getMockBuilder(ActiveRecord\Model::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_primary', 'where' ])
			->getMock();
		$model
			->expects($this->once())
			->method('get_primary')
			->willReturn($primary);
		$model
			->expects($this->once())
			->method('where')
			->with($key ? [ $column => $value, "!$primary" => $key ] : [ $column => $value ])
			->willReturn($query);

		$record = new ActiveRecord($model);
		$record->$primary = $key;

		/* @var $record ActiveRecord */

		return new RecordAdapter($record);
	}
}
