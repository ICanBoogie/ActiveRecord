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

class ScopeNotDefinedTest extends \PHPUnit_Framework_TestCase
{
	static private $model;
	static private $exception;

	static public function setupBeforeClass()
	{
		self::$model = new Model([

			Model::CONNECTION => new Connection('sqlite::memory:'),
			Model::NAME => 'testing',
			Model::SCHEMA => [

				'fields' => [

					'id' => 'serial'

				]

			]

		]);

		self::$exception = new ScopeNotDefined('my_scope', self::$model);
	}

	public function test_message()
	{
		$this->assertEquals("Unknown scope `my_scope` for model `testing`.", self::$exception->getMessage());
	}

	public function test_get_scope_name()
	{
		$this->assertEquals('my_scope', self::$exception->scope_name);
	}

	public function test_get_model()
	{
		$this->assertSame(self::$model, self::$exception->model);
	}
}
