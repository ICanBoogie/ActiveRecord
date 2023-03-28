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

use DateTimeInterface;
use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\ActiveRecord\Connection;
use ICanBoogie\ActiveRecord\Driver;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaColumn;
use ICanBoogie\ActiveRecord\SchemaIndex;
use ICanBoogie\DateTime;

/**
 * Basic connection driver.
 *
 * @property-read Connection $connection
 */
abstract class BasicDriver implements Driver
{
    /**
     * @uses get_connection
     */
    use AccessorTrait;

    /**
     * @var callable
     */
    private $connection_provider;

    private function get_connection(): Connection
    {
        return ($this->connection_provider)();
    }

    /**
     * @param callable $connection_provider A callable that provides a database connection.
     */
    public function __construct(callable $connection_provider)
    {
        $this->connection_provider = $connection_provider;
    }

    /**
     * @inheritdoc
     */
    public function quote_string(string $string): string
    {
        return $this->connection->pdo->quote($string);
    }

    /**
     * @inheritDoc
     */
    public function quote_identifier(string $identifier): string
    {
        return "`$identifier`";
    }

    /**
     * @inheritDoc
     */
    public function cast_value(mixed $value, string $type = null): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return DateTime::from($value)->utc->as_db;
        }

        if ($value === false) {
            return 0;
        }

        if ($value === true) {
            return 1;
        }

        return $value;
    }

    public function create_table(string $table_name, Schema $schema): void
    {
        $this->connection->exec(
            $this->render_create_table($table_name, $schema)
        );
    }

    /**
     * Renders the statement to create the specified table.
     */
    abstract protected function render_create_table(string $table_name, Schema $schema): string;

    public function create_indexes(string $table_name, Schema $schema): void
    {
        foreach ($schema->indexes as $index) {
            $this->connection->exec(
                $this->render_create_index($table_name, $index)
            );
        }
    }

    /**
     * Renders the statement to create the specified index.
     */
    abstract protected function render_create_index(string $table_name, SchemaIndex $index): string;

    /**
     * @return string[]
     */
    protected function render_create_table_lines(Schema $schema): array
    {
        $lines = [];

        foreach ($schema->columns as $column_id => $column) {
            $lines[$column_id] = $this->render_create_table_line($schema, $column_id, $column);
        }

        return $lines;
    }

    protected function render_create_table_line(Schema $schema, string $column_id, SchemaColumn $column): string
    {
        return $this->quote_identifier($column_id) . " " . $this->render_column($column);
    }

    /**
     * Renders the column definition.
     */
    abstract protected function render_column(SchemaColumn $column): string;
}
