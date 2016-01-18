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
use ICanBoogie\ActiveRecord\CreatedAtPropertyTest\A;
use ICanBoogie\ActiveRecord\CreatedAtPropertyTest\B;

class CreatedAtPropertyTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider provide_test_property
	 */
	public function test_property($classname)
	{
		/* @var $r A */
		$r = new $classname;
		$datetime = new DateTime();

		$this->assertInstanceOf(DateTime::class, $r->created_at);
		$this->assertTrue($r->created_at->is_empty);

		$r->created_at = $datetime;

		$this->assertSame($datetime, $r->created_at);
		$this->assertArrayHasKey('created_at', $r->to_array());
		$this->assertSame($datetime, $r->to_array()['created_at']);
		$this->assertArrayHasKey('created_at', $r->__sleep());

		$r->created_at = null;
		$this->assertInstanceOf(DateTime::class, $r->created_at);
		$this->assertTrue($r->created_at->is_empty);
	}

	public function provide_test_property()
	{
		return [

			[ A::class ],
			[ B::class ]

		];
	}
}

namespace ICanBoogie\ActiveRecord\CreatedAtPropertyTest;

class A extends \ICanBoogie\Prototyped
{
	use \ICanBoogie\ActiveRecord\CreatedAtProperty;
}

class B extends A
{

}
