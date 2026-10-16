<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use ICanBoogie\ActiveRecord\Connection;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Test\ICanBoogie\Fixtures;

use function array_map;
use function array_values;
use function is_scalar;

#[Group("db")]
final class ConnectionTest extends TestCase
{
    private string $id;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->id = 'db' . uniqid();

        $this->connection = new Connection(
            new ConnectionDefinition(
                id: $this->id,
                dsn: Fixtures::dsn(),
                username: Fixtures::username(),
                password: Fixtures::password(),
                charset_and_collate: 'ascii/bin',
                time_zone: '+02:30',
            )
        );
    }

    public function test_get_id(): void
    {
        $this->assertSame($this->id, $this->connection->id);
    }

    public function test_get_charset(): void
    {
        $this->assertSame('ascii', $this->connection->charset);
    }

    public function test_get_collate(): void
    {
        $this->assertSame('ascii_bin', $this->connection->collate);
    }

    public function test_get_timezone(): void
    {
        $this->assertSame('+02:30', $this->connection->timezone);
    }

    public function test_quote_identifier(): void
    {
        $quote = $this->connection->driver_name === 'pgsql' ? '"identifier"' : '`identifier`';

        $this->assertSame($quote, $this->connection->quote_identifier('identifier'));
    }

    public function test_transaction_commits_and_returns_the_result(): void
    {
        $table = $this->create_transaction_table();

        $result = $this->connection->transaction(function () use ($table) {
            $this->connection->pdo->exec("INSERT INTO $table (id) VALUES (1)");

            $this->assertTrue($this->connection->pdo->inTransaction());

            return 'done';
        });

        $this->assertSame('done', $result);
        $this->assertFalse($this->connection->pdo->inTransaction());
        $this->assertSame(1, $this->count_rows($table));
    }

    public function test_transaction_rolls_back_when_the_callback_throws(): void
    {
        $table = $this->create_transaction_table();

        $message = null;

        try {
            $this->connection->transaction(function () use ($table) {
                $this->connection->pdo->exec("INSERT INTO $table (id) VALUES (1)");

                throw new RuntimeException("Oops");
            });
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
        }

        $this->assertSame("Oops", $message);

        $this->assertFalse($this->connection->pdo->inTransaction());
        $this->assertSame(0, $this->count_rows($table));
    }

    public function test_nested_transaction_is_released_into_the_outer_transaction(): void
    {
        $table = $this->create_transaction_table();

        $this->connection->begin();
        $this->connection->transaction(
            fn() => $this->connection->pdo->exec("INSERT INTO $table (id) VALUES (1)")
        );

        $this->assertTrue($this->connection->pdo->inTransaction(), "the outer transaction is not committed");
        $this->assertSame(1, $this->count_rows($table));

        $this->connection->pdo->rollBack();

        $this->assertSame(0, $this->count_rows($table));
    }

    public function test_nested_transaction_rolls_back_only_its_own_work(): void
    {
        $table = $this->create_transaction_table();

        $this->connection->transaction(function () use ($table) {
            $this->connection->pdo->exec("INSERT INTO $table (id) VALUES (1)");

            try {
                $this->connection->transaction(function () use ($table) {
                    $this->connection->pdo->exec("INSERT INTO $table (id) VALUES (2)");

                    throw new RuntimeException("Oops");
                });
            } catch (RuntimeException) {
                // The outer transaction carries on.
            }

            $this->connection->pdo->exec("INSERT INTO $table (id) VALUES (3)");
        });

        $this->assertSame([ 1, 3 ], $this->ids($table));
    }

    public function test_nested_transactions_use_distinct_savepoints(): void
    {
        $table = $this->create_transaction_table();

        $this->connection->transaction(function () use ($table) {
            $this->connection->transaction(function () use ($table) {
                $this->connection->pdo->exec("INSERT INTO $table (id) VALUES (1)");

                try {
                    $this->connection->transaction(function () use ($table) {
                        $this->connection->pdo->exec("INSERT INTO $table (id) VALUES (2)");

                        throw new RuntimeException("Oops");
                    });
                } catch (RuntimeException) {
                    // expected
                }
            });

            $this->connection->transaction(
                fn() => $this->connection->pdo->exec("INSERT INTO $table (id) VALUES (3)")
            );
        });

        $this->assertSame([ 1, 3 ], $this->ids($table));
    }

    public function test_failing_nested_transaction_rolls_back_everything_when_not_caught(): void
    {
        $table = $this->create_transaction_table();

        try {
            $this->connection->transaction(function () use ($table) {
                $this->connection->pdo->exec("INSERT INTO $table (id) VALUES (1)");

                $this->connection->transaction(function () use ($table) {
                    $this->connection->pdo->exec("INSERT INTO $table (id) VALUES (2)");

                    throw new RuntimeException("Oops");
                });
            });
        } catch (RuntimeException) {
            // expected
        }

        $this->assertFalse($this->connection->pdo->inTransaction());
        $this->assertSame([], $this->ids($table));
    }

    private function create_transaction_table(): string
    {
        $table = 'tx' . uniqid();

        $this->connection->pdo->exec("CREATE TABLE $table (id INTEGER)");

        return $table;
    }

    private function count_rows(string $table): int
    {
        $statement = $this->connection->pdo->query("SELECT COUNT(*) FROM $table");
        assert($statement !== false);

        return (int) $statement->fetchColumn();
    }

    /**
     * @return list<int>
     */
    private function ids(string $table): array
    {
        $statement = $this->connection->pdo->query("SELECT id FROM $table ORDER BY id");
        assert($statement !== false);

        return array_values(array_map(fn(mixed $id) => (int) (is_scalar($id) ? $id : 0), $statement->fetchAll(PDO::FETCH_COLUMN)));
    }
}
