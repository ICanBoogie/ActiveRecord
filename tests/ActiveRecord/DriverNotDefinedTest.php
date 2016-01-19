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

class DriverNotDefinedTest extends \PHPUnit_Framework_TestCase
{
	public function test_exception()
	{
		$driver_name = uniqid();
		$exception = new DriverNotDefined($driver_name);
		$this->assertSame($driver_name, $exception->driver_name);
		$this->assertContains($driver_name, $exception->getMessage());
	}
}
