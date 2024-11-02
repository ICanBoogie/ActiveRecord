<?php

namespace ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Schema;

use function implode;

/**
 * Connection driver for MySQL.
 */
final class MySQLDriver extends BasicDriver
{
    /**
     * @inheritDoc
     */
    protected function render_create_table(string $table_name, Schema $schema): string
    {
        return (new TableRendererForMySQL())
            ->render($schema, $table_name);
    }

    /**
     * @inheritdoc
     */
    public function table_exists(string $table_name): bool
    {
        $tables = $this->connection->query('SHOW TABLES')->all(\PDO::FETCH_COLUMN);

        return \in_array($table_name, $tables);
    }

    /**
     * @inheritdoc
     */
    public function optimize(): void
    {
        $connection = $this->connection;
        $tables = $connection->query('SHOW TABLES')->all(\PDO::FETCH_COLUMN);
        $connection->exec('OPTIMIZE TABLE ' . implode(', ', $tables));
    }
}
