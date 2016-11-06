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
use ICanBoogie\ActiveRecord\DateTimePropertiesTest\A;
use ICanBoogie\ActiveRecord\DateTimePropertiesTest\B;

class DateTimePropertiesTest extends \PHPUnit\Framework\TestCase
{
	public function test_properties()
	{
		$r = new A();
		$created_at = new DateTime('-10 day');
		$updated_at = new DateTime('-1 day');

		$this->assertInstanceOf(DateTime::class, $r->created_at);
		$this->assertInstanceOf(DateTime::class, $r->updated_at);
		$this->assertTrue($r->created_at->is_empty);
		$this->assertTrue($r->updated_at->is_empty);

		$r->created_at = $created_at;
		$r->updated_at = $updated_at;

		$this->assertSame($created_at, $r->created_at);
		$this->assertSame($updated_at, $r->updated_at);
		$this->assertArrayHasKey('created_at', $r->to_array());
		$this->assertArrayHasKey('updated_at', $r->to_array());
		$this->assertSame($created_at, $r->to_array()['created_at']);
		$this->assertSame($updated_at, $r->to_array()['updated_at']);
		$this->assertArrayHasKey('created_at', $r->__sleep());
		$this->assertArrayHasKey('updated_at', $r->__sleep());

		$r->created_at = null;
		$r->updated_at = null;
		$this->assertInstanceOf(DateTime::class, $r->created_at);
		$this->assertInstanceOf(DateTime::class, $r->updated_at);
		$this->assertTrue($r->created_at->is_empty);
		$this->assertTrue($r->updated_at->is_empty);
	}

	public function test_properties_extended()
	{
		$r = new B;
		$created_at = new DateTime('-10 day');
		$updated_at = new DateTime('-1 day');
		$datetime = new DateTime();

		$this->assertInstanceOf(DateTime::class, $r->created_at);
		$this->assertInstanceOf(DateTime::class, $r->updated_at);
		$this->assertInstanceOf(DateTime::class, $r->datetime);
		$this->assertTrue($r->created_at->is_empty);
		$this->assertTrue($r->updated_at->is_empty);
		$this->assertTrue($r->datetime->is_empty);

		$r->created_at = $created_at;
		$r->updated_at = $updated_at;
		$r->datetime = $datetime;

		$this->assertSame($created_at, $r->created_at);
		$this->assertSame($updated_at, $r->updated_at);
		$this->assertSame($datetime, $r->datetime);
		$this->assertArrayHasKey('created_at', $r->to_array());
		$this->assertArrayHasKey('updated_at', $r->to_array());
		$this->assertArrayHasKey('datetime', $r->to_array());
		$this->assertSame($created_at, $r->to_array()['created_at']);
		$this->assertSame($updated_at, $r->to_array()['updated_at']);
		$this->assertSame($datetime, $r->to_array()['datetime']);
		$this->assertArrayHasKey('created_at', $r->__sleep());
		$this->assertArrayHasKey('updated_at', $r->__sleep());
		$this->assertArrayHasKey('datetime', $r->__sleep());

		$r->created_at = null;
		$r->updated_at = null;
		$r->datetime = null;
		$this->assertInstanceOf(DateTime::class, $r->created_at);
		$this->assertInstanceOf(DateTime::class, $r->updated_at);
		$this->assertInstanceOf(DateTime::class, $r->datetime);
		$this->assertTrue($r->created_at->is_empty);
		$this->assertTrue($r->updated_at->is_empty);
		$this->assertTrue($r->datetime->is_empty);
	}
}

namespace ICanBoogie\ActiveRecord\DateTimePropertiesTest;

class A extends \ICanBoogie\Prototyped
{
	use \ICanBoogie\ActiveRecord\Property\CreatedAtProperty;
	use \ICanBoogie\ActiveRecord\Property\UpdatedAtProperty;
}

class B extends A
{
	use \ICanBoogie\ActiveRecord\Property\DateTimeProperty;
}
