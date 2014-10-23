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

class StatementTest extends \PHPUnit_Framework_TestCase
{
	static private $connection;

	static public function setupBeforeClass()
	{
		self::$connection = $connection = new Connection('sqlite::memory:');

		$connection('CREATE TABLE test (a INTEGER PRIMARY KEY ASC, b INTEGER, c VARCHAR(20))');
		$connection('INSERT INTO test (b,c) VALUES(1, "one")');
		$connection('INSERT INTO test (b,c) VALUES(2, "two")');
		$connection('INSERT INTO test (b,c) VALUES(3, "three")');
	}

	public function test_get_statement()
	{
		$connection = self::$connection;
		$statement = $connection('SELECT * FROM test');
		$this->assertInstanceOf('ICanBoogie\ActiveRecord\Statement', $statement);

		$records = $statement->all;
		$this->assertInternalType('array', $records);
		$this->assertCount(3, $records);
	}

	/**
	 * Query statements are always prepared, so if the preparation fails a StatementNotValid
	 * expection is thrown with the only the statement string and not the arguments,
	 * which are applied later when the preparation is successful.
	 *
	 */
	public function test_invalid_prepared_statement()
	{
		$connection = self::$connection;
		$statement = 'SELECT undefined_column FROM test WHERE b = ?';

		try
		{
			$statement = $connection($statement, [ 3 ]);

			$this->fail('Expected StatementNotValid excpetion');
		}
		catch (\Exception $e)
		{
			$this->assertInstanceOf('ICanBoogie\ActiveRecord\StatementNotValid', $e);
			$this->assertInstanceOf('PDOException', $e->original);
			$this->assertNull($e->getPrevious());

			$this->assertInternalType('string', $e->statement);
			$this->assertEquals($statement, $e->statement);
			$this->assertNull($e->args);
			$this->assertEquals('HY000(1) no such column: undefined_column — `SELECT undefined_column FROM test WHERE b = ?`', $e->getMessage());
		}
	}

	/**
	 * Query statements are always prepared, so if the query fails during its execution a
	 * StatementNotValid expection is thrown with the Statement instance and its arguments.
	 */
	public function test_invalid_query()
	{
		$connection = self::$connection;
		$statement = 'INSERT INTO test (a,b,c) VALUES(1,?,?)';

		try
		{
			$statement = $connection($statement, [ 1, "oneone" ]);

			$this->fail('Expected StatementNotValid excpetion');
		}
		catch (\Exception $e)
		{
			$this->assertInstanceOf('ICanBoogie\ActiveRecord\StatementNotValid', $e);
			$this->assertInstanceOf('PDOException', $e->original);
			$this->assertNull($e->getPrevious());

			$this->assertInstanceOf('ICanBoogie\ActiveRecord\Statement', $e->statement);
			$this->assertEquals($statement, $e->statement);
			$this->assertSame([ 1, "oneone"], $e->args);

			#
			# Only test the start and the end of the exception message since the it could
			# read "PRIMARY KEY must be unique" or "UNIQUE constraint failed" depending on the
			# SQLite driver or driver version.
			#

			$this->assertStringStartsWith('23000(19)', $e->getMessage());
			$this->assertStringEndsWith('`INSERT INTO test (a,b,c) VALUES(1,?,?)` [1,"oneone"]', $e->getMessage());
		}
	}

	/**
	 * Exec statements are not prepared, if the execution fails a StatementNotValid
	 * expection is thrown with the only the statement string.
	 */
	public function test_invalid_exec()
	{
		$connection = self::$connection;
		$statement = 'DELETE FROM undefined_table';

		try
		{
			$statement = $connection($statement);

			$this->fail('Expected StatementNotValid excpetion');
		}
		catch (\Exception $e)
		{
			$this->assertInstanceOf('ICanBoogie\ActiveRecord\StatementNotValid', $e);
			$this->assertInstanceOf('PDOException', $e->original);
			$this->assertNull($e->getPrevious());

			$this->assertInternalType('string', $e->statement);
			$this->assertEquals($statement, $e->statement);
			$this->assertNull($e->args);
			$this->assertEquals('HY000(1) no such table: undefined_table — `DELETE FROM undefined_table`', $e->getMessage());
		}
	}
}
