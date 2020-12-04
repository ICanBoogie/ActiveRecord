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

class RecordNotFoundTest extends \PHPUnit\Framework\TestCase
{
	static private $records;

	/**
	 * @var RecordNotFound
	 */
	static private $exception;

	static public function setupBeforeClass(): void
	{
		self::$records = [

			1 => new ActiveRecord('fake-model-id'),
			2 => false,
			3 => new ActiveRecord('fake-model-id')

		];

		self::$exception = new RecordNotFound("My message", self::$records);
	}

	public function test_message()
	{
		$this->assertEquals("My message", self::$exception->getMessage());
	}

	public function test_get_records()
	{
		$this->assertIsArray(self::$exception->records);
		$this->assertSame(self::$records, self::$exception->records);
	}
}
