<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\ActiveRecord;

use Exception;
use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use ICanBoogie\ActiveRecord\Connection;
use ICanBoogie\ActiveRecord\Statement;
use ICanBoogie\ActiveRecord\StatementInvocationFailed;
use ICanBoogie\ActiveRecord\StatementNotValid;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Throwable;

final class StatementTest extends TestCase
{
    private static Connection $connection;

    public static function setupBeforeClass(): void
    {
        self::$connection = $connection = new Connection(new ConnectionDefinition('id', 'sqlite::memory:'));

        $connection->exec(<<<SQL
        CREATE TABLE test (a INTEGER PRIMARY KEY ASC, b INTEGER, c VARCHAR(20));
        INSERT INTO test (b,c) VALUES(1, "one");
        INSERT INTO test (b,c) VALUES(2, "two");
        INSERT INTO test (b,c) VALUES(3, "three");
        SQL);
    }

    public function test_get_statement(): void
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
     * exception is thrown with the statement string and not the arguments,
     * which are applied later when the preparation is successful.
     *
     */
    public function test_invalid_prepared_statement(): void
    {
        $connection = self::$connection;
        $statement = 'SELECT undefined_column FROM test WHERE b = ?';

        try {
            $statement = $connection($statement, [ 3 ]);

            $this->fail("Expected " . StatementNotValid::class);
        } catch (StatementNotValid $e) {
            $this->assertInstanceOf(PDOException::class, $e->original);
            $this->assertNull($e->getPrevious());

            $this->assertIsString($e->statement);
            $this->assertEquals($statement, $e->statement);
            $this->assertEmpty($e->args);
            $this->assertEquals(
                'HY000(1) no such column: undefined_column — `SELECT undefined_column FROM test WHERE b = ?`',
                $e->getMessage()
            );
        } catch (Throwable $e) {
            $this->fail("Expected " . StatementNotValid::class . ", got: " . $e::class);
        }
    }

    /**
     * Query statements are always prepared, so if the query fails during its execution a
     * StatementNotValid exception is thrown with the Statement instance and its arguments.
     */
    public function test_invalid_query(): void
    {
        $connection = self::$connection;
        $statement = 'INSERT INTO test (a,b,c) VALUES(1,?,?)';

        try {
            $statement = $connection($statement, [ 1, "oneone" ]);

            $this->fail('Expected StatementNotValid excpetion');
        } catch (Exception $e) {
            $this->assertInstanceOf(StatementNotValid::class, $e);

            /* @var $e StatementNotValid */

            $this->assertInstanceOf(PDOException::class, $e->original);
            $this->assertNull($e->getPrevious());

            $this->assertEquals($statement, $e->statement);
            $this->assertSame([ 1, "oneone" ], $e->args);

            #
            # Only test the start and the end of the exception message since it could
            # read "PRIMARY KEY must be unique" or "UNIQUE constraint failed" depending on the
            # SQLite driver or driver version.
            #

            $this->assertStringStartsWith('23000(19)', $e->getMessage());
            $this->assertStringEndsWith('`INSERT INTO test (a,b,c) VALUES(1,?,?)` [1,"oneone"]', $e->getMessage());
        }
    }

    /**
     * Exec statements are not prepared, if the execution fails a StatementNotValid
     * exception is thrown with only the statement string.
     */
    public function test_invalid_exec(): void
    {
        $connection = self::$connection;
        $statement = 'DELETE FROM undefined_table';

        try {
            $statement = $connection($statement);

            $this->fail('Expected StatementNotValid');
        } catch (StatementNotValid $e) {
            $this->assertInstanceOf(PDOException::class, $e->original);
            $this->assertNull($e->getPrevious());

            $this->assertEquals($statement, $e->statement);
            $this->assertEmpty($e->args);
            $this->assertEquals(
                'HY000(1) no such table: undefined_table — `DELETE FROM undefined_table`',
                $e->getMessage()
            );
        } catch (Throwable) {
            $this->fail('Expected StatementNotValid');
        }
    }

    // @phpstan-ignore-next-line
    #[DataProvider("provide_modes")]
    public function test_mode(array $arguments): void
    {
        $this->markTestSkipped("Statement is final");

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

    // @phpstan-ignore-next-line
    public static function provide_modes(): array
    {
        return [

            [ [ PDO::FETCH_ASSOC ] ],
            [ [ PDO::FETCH_COLUMN, 123 ] ],
            [ [ PDO::FETCH_LAZY ] ],
            [ [ PDO::FETCH_CLASS, Article::class ] ],

        ];
    }

    // @phpstan-ignore-next-line
    #[DataProvider("provide_modes")]
    public function test_fetchAndClose($arguments)
    {
        $this->markTestSkipped("Statement is final");

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
        $a->with(...$arguments);

        /* @var $statement Statement */
        $this->assertSame($expected, call_user_func_array([ $statement, 'one' ], $arguments));
    }

    public function test_get_rc()
    {
        $this->markTestSkipped("Statement is final");

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

    public function test_get_one()
    {
        $this->markTestSkipped("Statement is final");

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

    // @php-stan-ignore-next-line
    #[DataProvider("provide_modes")]
    public function test_all($arguments): void
    {
        $this->markTestSkipped("Statement is final");

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

    public function test_get_all(): void
    {
        $this->markTestSkipped("Statement is final");

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

    public function test_get_pairs(): void
    {
        $this->markTestSkipped("Statement is final");

        $pairs = [ 1 => "one", 2 => "tow" ];

        $statement = $this
            ->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'fetchAll' ])
            ->getMock();
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_KEY_PAIR)
            ->willReturn($pairs);

        /* @var $statement Statement */
        $this->assertSame($pairs, $statement->pairs);
    }
}
