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

class ActiveRecordClassNotValidTest extends \PHPUnit\Framework\TestCase
{
	public function test_get_class()
	{
		$expected = ActiveRecord::class;
		$e = new ActiveRecordClassNotValid($expected);
		$this->assertEquals($expected, $e->class);
	}
}
