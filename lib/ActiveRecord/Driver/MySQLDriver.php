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

use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaColumn;
use ICanBoogie\ActiveRecord\SchemaIndex;

use function array_filter;
use function array_map;
use function implode;
use function is_array;

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
        $connection = $this->connection;
        $quoted_table_name = $this->quote_identifier($table_name);
        $lines = $this->render_create_table_lines($schema);
        $lines = implode(",\n    ", array_filter($lines));

        $maybeCollate = $connection->collate ? " COLLATE $connection->collate" : '';

        return <<<SQL
        CREATE TABLE $quoted_table_name (
            $lines
        ){$maybeCollate}
        SQL;
    }

    /**
     * @inheritDoc
     */
    protected function render_create_index(string $table_name, SchemaIndex $index): string
    {
        $quoted_table_name = $this->quote_identifier($table_name);
        $maybeUnique = $index->unique ? 'UNIQUE ' : '';
        $index_name = $this->quote_identifier($index->name ?? implode('_', $index->columns));
        $indexed_columns = implode(', ', array_map(
            fn($column_id) => $this->quote_identifier($column_id),
            $index->columns
        ));

        return <<<SQL
            CREATE {$maybeUnique}INDEX $index_name ON $quoted_table_name ($indexed_columns)
            SQL;
    }

    /**
     * @inheritDoc
     */
    public function render_column(SchemaColumn $column): string
    {
        return (string) $column;
    }

    /**
     * @inheritdoc
     */
    public function table_exists(string $name): bool
    {
        $tables = $this->connection->query('SHOW TABLES')->all(\PDO::FETCH_COLUMN);

        return \in_array($name, $tables);
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

    /**
     * Renders primary key clause to create table.
     */
    private function render_create_table_primary_key(Schema $schema): string
    {
        $primary = $schema->primary;

        if (!$primary) {
            return '';
        }

        if (is_array($primary)) {
            $quoted_primary_key = implode(', ', array_map(
                fn(string $column_id) => $this->quote_identifier($column_id), $primary
            ));
        } else {
            $quoted_primary_key = $this->quote_identifier($primary);
        }

        return "PRIMARY KEY($quoted_primary_key)";
    }
}
