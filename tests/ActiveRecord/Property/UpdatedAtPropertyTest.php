<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Property;

use ICanBoogie\DateTime;
use ICanBoogie\ActiveRecord\UpdatedAtPropertyTest\A;
use ICanBoogie\ActiveRecord\UpdatedAtPropertyTest\B;

class UpdatedAtPropertyTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider provide_test_property
	 */
	public function test_property($classname)
	{
		/* @var $r A|B */
		$r = new $classname;
		$datetime = new DateTime();

		$this->assertInstanceOf(DateTime::class, $r->updated_at);
		$this->assertTrue($r->updated_at->is_empty);

		$r->updated_at = $datetime;

		$this->assertSame($datetime, $r->updated_at);
		$this->assertArrayHasKey('updated_at', $r->to_array());
		$this->assertSame($datetime, $r->to_array()['updated_at']);
		$this->assertArrayHasKey('updated_at', $r->__sleep());

		$r->updated_at = null;
		$this->assertInstanceOf(DateTime::class, $r->updated_at);
		$this->assertTrue($r->updated_at->is_empty);
	}

	public function provide_test_property()
	{
		return [

			[ A::class ],
			[ B::class ]

		];
	}
}

namespace ICanBoogie\ActiveRecord\UpdatedAtPropertyTest;

class A extends \ICanBoogie\Prototyped
{
	use \ICanBoogie\ActiveRecord\Property\UpdatedAtProperty;
}

class B extends A
{

}
