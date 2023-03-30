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
}
