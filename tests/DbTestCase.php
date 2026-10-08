<?php

namespace Test\ICanBoogie;

use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use ICanBoogie\ActiveRecord\Connection;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that touch the database.
 *
 * Unlike SQLite's `sqlite::memory:`, PostgreSQL and MySQL databases are shared and persistent.
 * To isolate tests from each other, all tables are dropped after each test.
 */
abstract class DbTestCase extends TestCase
{
    private static ?Connection $reset_connection = null;

    protected function tearDown(): void
    {
        self::drop_all_tables();

        // A Connection holds a reference cycle (connection -> driver -> closure -> connection),
        // so its PDO connection is only released by the cycle collector. Run it now so we don't
        // exhaust the server connection pool.
        gc_collect_cycles();

        parent::tearDown();
    }

    private static function drop_all_tables(): void
    {
        $connection = self::$reset_connection ??= new Connection(
            new ConnectionDefinition(
                id: 'reset',
                dsn: Fixtures::dsn(),
                username: Fixtures::username(),
                password: Fixtures::password(),
            )
        );

        $pdo = $connection->pdo;
        $driver_name = $connection->driver_name;

        $tables = match ($driver_name) {
            'sqlite' => $pdo
                ->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")
                ->fetchAll(PDO::FETCH_COLUMN),
            'pgsql' => $pdo
                ->query('SELECT tablename FROM pg_tables WHERE schemaname = current_schema()')
                ->fetchAll(PDO::FETCH_COLUMN),
            'mysql' => $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN),
        };

        $suffix = $driver_name === 'pgsql' ? ' CASCADE' : '';

        foreach ($tables as $table) {
            $pdo->exec('DROP TABLE ' . $connection->quote_identifier((string) $table) . $suffix);
        }
    }
}
