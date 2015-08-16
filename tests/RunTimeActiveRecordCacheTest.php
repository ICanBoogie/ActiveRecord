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

class RunTimeActiveRecordCacheTest extends \PHPUnit_Framework_TestCase
{
	public function test_cache()
	{
		$primary = 'id';
		$key = 123;

		$model = $this
			->getMockBuilder(Model::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_primary'])
			->getMock();
		$model
			->expects($this->any())
			->method('get_primary')
			->willReturn($primary);

		/* @var $model Model */

		$record = new ActiveRecord($model);
		$record->$primary = $key;

		$this->assertSame($primary, $model->primary);

		$cache = new RuntimeActiveRecordCache($model);
		$cache->store($record);
		$this->assertSame($record, $cache->retrieve($key));
		$cache->store($record);
		$this->assertSame($record, $cache->retrieve($key));

		$cache->eliminate($key);
		$this->assertEmpty($cache->retrieve($key));

		$cache->store($record);
		$this->assertSame($record, $cache->retrieve($key));
		$cache->clear();
		$this->assertEmpty($cache->retrieve($key));

		foreach ($cache as $k => $r)
		{
			$this->assertSame($key, $k);
			$this->assertSame($record, $r);
		}
	}
}
