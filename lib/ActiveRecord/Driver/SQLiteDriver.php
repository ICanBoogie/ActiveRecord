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

/**
 * Connection driver for SQLite.
 */
class SQLiteDriver extends MySQLDriver
{
    /**
     * @inheritdoc
     */
    public function render_column(SchemaColumn $column): string
    {
        if ($column->primary && $column->type == SchemaColumn::TYPE_INTEGER) {
            return "INTEGER NOT NULL";
        }

        return \implode(' ', \array_filter([

            $column->formatted_type,
            $column->formatted_attributes,
            $column->formatted_null,
            $column->formatted_auto_increment,
            $column->formatted_default,
            $column->formatted_comment

        ]));
    }

    /**
     * @inheritdoc
     */
    protected function render_create_table(string $unprefixed_table_name, Schema $schema): string
    {
        $quoted_table_name = $this->resolve_quoted_table_name($unprefixed_table_name);
        $lines = $this->render_create_table_lines($schema);
        $lines[] = $this->render_create_table_primary_key($schema);

        return "CREATE TABLE $quoted_table_name\n(\n\t" . \implode(",\n\t", \array_filter($lines)) . "\n)";
    }

    /**
     * @inheritdoc
     */
    public function table_exists(string $unprefixed_name): bool
    {
        $name = $this->resolve_table_name($unprefixed_name);

        $tables = $this->connection
            ->query('SELECT name FROM sqlite_master WHERE type = "table" AND name = ?', [ $name ])
            ->all(\PDO::FETCH_COLUMN);

        return !!$tables;
    }

    /**
     * @inheritdoc
     */
    public function optimize(): void
    {
        $this->connection->exec('VACUUM');
    }

    /**
     * @inheritdoc
     */
    protected function resolve_index_name(string $unprefixed_table_name, string $index_id): string
    {
        return $index_id . '_' . \substr(\sha1($unprefixed_table_name), 0, 8);
    }
}
