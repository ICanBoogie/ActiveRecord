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

use ICanBoogie\ActiveRecord\ModelTest\Article;
use PHPUnit\Framework\TestCase;

class StatementTest extends TestCase
{
    private static $connection;

    public static function setupBeforeClass(): void
    {
        self::$connection = $connection = new Connection('sqlite::memory:');

        $connection('CREATE TABLE test (a INTEGER PRIMARY KEY ASC, b INTEGER, c VARCHAR(20))');
        $connection('INSERT INTO test (b,c) VALUES(1, "one")');
        $connection('INSERT INTO test (b,c) VALUES(2, "two")');
        $connection('INSERT INTO test (b,c) VALUES(3, "three")');
    }

    protected function setUp(): void
    {
        $this->markTestSkipped();
    }

    public function test_get_statement()
    {
        $connection = self::$connection;
        $statement = $connection('SELECT * FROM test');
        $this->assertInstanceOf(Statement::class, $statement);

        $records = $statement->all;
        $this->assertIsArray($records);
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

        try {
            $statement = $connection($statement, [ 3 ]);

            $this->fail('Expected StatementNotValid excpetion');
        } catch (\Exception $e) {
            $this->assertInstanceOf(StatementNotValid::class, $e);

            /* @var $e StatementNotValid */

            $this->assertInstanceOf(\PDOException::class, $e->original);
            $this->assertNull($e->getPrevious());

            $this->assertIsString($e->statement);
            $this->assertEquals($statement, $e->statement);
            $this->assertEmpty($e->args);
            $this->assertEquals(
                'HY000(1) no such column: undefined_column — `SELECT undefined_column FROM test WHERE b = ?`',
                $e->getMessage()
            );
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

        try {
            $statement = $connection($statement, [ 1, "oneone" ]);

            $this->fail('Expected StatementNotValid excpetion');
        } catch (\Exception $e) {
            $this->assertInstanceOf(StatementNotValid::class, $e);

            /* @var $e StatementNotValid */

            $this->assertInstanceOf(\PDOException::class, $e->original);
            $this->assertNull($e->getPrevious());

            $this->assertEquals($statement, $e->statement);
            $this->assertSame([ 1, "oneone" ], $e->args);

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

        try {
            $statement = $connection($statement);

            $this->fail('Expected StatementNotValid');
        } catch (StatementNotValid $e) {
            $this->assertInstanceOf(\PDOException::class, $e->original);
            $this->assertNull($e->getPrevious());

            $this->assertIsString($e->statement);
            $this->assertEquals($statement, $e->statement);
            $this->assertEmpty($e->args);
            $this->assertEquals(
                'HY000(1) no such table: undefined_table — `DELETE FROM undefined_table`',
                $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->fail('Expected StatementNotValid');
        }
    }

    /**
     * @requires PHP 5.6.0
     */
    public function test_invoke_should_thow_exception_when_execute_returns_false()
    {
        $arg = uniqid();

        $statement = $this
            ->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'execute' ])
            ->getMock();
        $statement
            ->expects($this->once())
            ->method('execute')
            ->with([ $arg ])
            ->willReturn(false);

        /* @var $statement Statement */

        try {
            $statement($arg);
        } catch (StatementInvocationFailed $e) {
            $this->assertSame($statement, $e->statement);
            $this->assertSame([ $arg ], $e->args);
            $this->assertStringContainsString($arg, $e->getMessage());

            return;
        }

        $this->fail("Expected StatementInvocationFailed");
    }

    /**
     * @dataProvider provide_modes
     *
     * @param $arguments
     */
    public function test_mode($arguments)
    {
        $statement = $this
            ->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'setFetchMode' ])
            ->getMock();
        $a = $statement
            ->expects($this->once())
            ->method('setFetchMode')
            ->willReturn(true);
        $a->with(...$arguments);

        /* @var $statement Statement */
        $this->assertSame($statement, $statement->mode(...$arguments));
    }

    /**
     * @requires PHP 5.6.0
     * @dataProvider provide_modes
     *
     * @param $arguments
     */
    public function test_fetchAndClose($arguments)
    {
        $expected = uniqid();

        $statement = $this
            ->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'fetch' ])
            ->getMock();
        $a = $statement
            ->expects($this->once())
            ->method('fetch')
            ->willReturn($expected);
        call_user_func_array([ $a, 'with' ], $arguments);

        /* @var $statement Statement */
        $this->assertSame($expected, call_user_func_array([ $statement, 'one' ], $arguments));
    }

    /**
     * @requires PHP 5.6.0
     */
    public function test_get_rc()
    {
        $expected = uniqid();

        $statement = $this
            ->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'fetchColumn' ])
            ->getMock();
        $statement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($expected);

        /* @var $statement Statement */

        $this->assertSame($expected, $statement->rc);
    }

    /**
     * @requires PHP 5.6.0
     */
    public function test_get_one()
    {
        $expected = uniqid();

        $statement = $this
            ->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'one' ])
            ->getMock();
        $statement
            ->expects($this->once())
            ->method('one')
            ->willReturn($expected);

        /* @var $statement Statement */

        $this->assertSame($expected, $statement->one);
    }

    /**
     * @requires PHP 5.6.0
     * @dataProvider provide_modes
     *
     * @param $arguments
     */
    public function test_all($arguments)
    {
        $all = [ uniqid(), uniqid() ];

        $statement = $this
            ->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'fetchAll' ])
            ->getMock();
        $a = $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn($all);
        call_user_func_array([ $a, 'with' ], $arguments);

        /* @var $statement Statement */
        $this->assertSame($all, call_user_func_array([ $statement, 'all' ], $arguments));
    }

    /**
     * @requires PHP 5.6.0
     */
    public function provide_modes()
    {
        $model = $this
            ->getMockBuilder(Model::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [

            [ [ \PDO::FETCH_ASSOC ] ],
            [ [ \PDO::FETCH_COLUMN, 123 ] ],
            [
                [
                    \PDO::FETCH_FUNC,
                    function () {
                    }
                ]
            ],
            [ [ \PDO::FETCH_CLASS, Article::class ] ],
            [ [ \PDO::FETCH_CLASS, Article::class, [ $model ] ] ]

        ];
    }

    /**
     * @requires PHP 5.6.0
     */
    public function test_get_all()
    {
        $all = [ uniqid(), uniqid() ];

        $statement = $this
            ->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'fetchAll' ])
            ->getMock();
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn($all);

        /* @var $statement Statement */
        $this->assertSame($all, $statement->all);
    }

    /**
     * @requires PHP 5.6.0
     */
    public function test_get_pairs()
    {
        $pairs = [ 1 => "one", 2 => "tow" ];

        $statement = $this
            ->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'fetchAll' ])
            ->getMock();
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_KEY_PAIR)
            ->willReturn($pairs);

        /* @var $statement Statement */
        $this->assertSame($pairs, $statement->pairs);
    }
}
