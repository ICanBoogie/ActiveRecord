<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Tests\Connections;

use ICanBoogie\ActiveRecord\Connections;

class ConnectionsTest extends \PHPUnit_Framework_TestCase
{
	private $connections;

	public function setUp()
	{
		$this->connections = new Connections
		(
			array
			(
				'one' => array
				(
					'dsn' => 'sqlite::memory:'
				),

				'bad' => array
				(
					'dsn' => 'mysql:dbname=bad_database' . uniqid()
				)
			)
		);
	}

	public function testGetConnection()
	{
		$connection = $this->connections['one'];
		$this->assertInstanceOf('ICanBoogie\ActiveRecord\Connection', $connection);
	}

	public function testSetConnection()
	{
		$this->connections['two'] = array
		(
			'dsn' => 'sqlite::memory:'
		);

		$this->assertInstanceOf('ICanBoogie\ActiveRecord\Connection', $this->connections['two']);
	}

	public function testUnsetConnection()
	{
		$this->connections['two'] = array
		(
			'dsn' => 'sqlite::memory:'
		);

		unset($this->connections['two']);

		$this->assertFalse(isset($this->connections['two']));
	}

	/**
	 * @expectedException ICanBoogie\ActiveRecord\ConnectionNotDefined
	 */
	public function testConnectionNotDefined()
	{
		$this->connections['two'];
	}

	/**
	 * @expectedException ICanBoogie\ActiveRecord\ConnectionNotEstablished
	 */
	public function testConnectionNotEstablished()
	{
		$this->connections['bad'];
	}

	/**
	 * @depends testGetConnection
	 * @expectedException ICanBoogie\ActiveRecord\ConnectionAlreadyEstablished
	 */
	public function testConnectionAlreadyEstablished()
	{
		$connection = $this->connections['one'];
		$this->connections['one'] = array
		(
			'dsn' => 'mysql:dbname=testing'
		);
	}
}