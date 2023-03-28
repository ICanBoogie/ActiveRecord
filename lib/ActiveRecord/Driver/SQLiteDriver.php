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

/**
 * Connection driver for SQLite.
 */
final class SQLiteDriver extends BasicDriver
{
    /**
     * @inheritDoc
     */
    protected function render_create_table(string $table_name, Schema $schema): string
    {
        return (new TableRendererForSQLite())
            ->render($schema, $table_name);
    }

    /**
     * @inheritdoc
     */
    public function table_exists(string $name): bool
    {
        $tables = $this->connection
            ->query('SELECT name FROM sqlite_master WHERE type = "table" AND name = ?', [ $name ])
            ->all(\PDO::FETCH_COLUMN);

        return count($tables) > 0;
    }

    /**
     * @inheritdoc
     */
    public function optimize(): void
    {
        $this->connection->exec('VACUUM');
    }
}
