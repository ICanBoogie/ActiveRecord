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

class ConnectionNotEstablishedTest extends \PHPUnit\Framework\TestCase
{
	public function test_get_id()
	{
		$id = 'testing';
		$e = new ConnectionNotEstablished($id, "message");
		$this->assertEquals($id, $e->id);
	}
}
