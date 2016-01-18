<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Connection;
use ICanBoogie\ActiveRecord\Schema;

class MySQLDriverTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @requires PHP 5.6
	 */
	public function test_multicolumn_primary_key()
	{
		$connection = $this
			->getMockBuilder(Connection::class)
			->disableOriginalConstructor()
			->setMethods([ 'exec' ])
			->getMock();
		$connection
			->expects($this->any())
			->method('exec')
			->willReturnCallback(function($statement) {

				$this->assertContains("`id` BIGINT UNSIGNED NOT NULL", $statement);
				$this->assertContains("`name` VARCHAR( 255 ) NOT NULL", $statement);
				$this->assertContains("`value` TEXT NOT NULL", $statement);
				$this->assertContains("PRIMARY KEY(`id`, `name`)", $statement);

			});

		/* @var $connection Connection */

		$schema = new Schema([

			'id' => [ 'foreign', 'primary' => true ],
			'name' => [ 'varchar', 'primary' => true ],
			'value' => 'text'

		]);

		$driver = new MySQLDriver(function() use ($connection) {

			return $connection;

		});

		$driver->create_table('test', $schema);
	}
}
