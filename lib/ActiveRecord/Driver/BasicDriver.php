<?php

namespace ICanBoogie\ActiveRecord\Driver;

use Closure;
use DateTimeInterface;
use ICanBoogie\ActiveRecord\Connection;
use ICanBoogie\ActiveRecord\Driver;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\DateTime;

/**
 * Basic connection driver.
 */
abstract class BasicDriver implements Driver
{
    public Connection $connection
    {
        get => ($this->connection_provider)();
    }

    /**
     * @param Closure():Connection $connection_provider
     *     A callable that provides a database connection.
     */
    public function __construct(
        private readonly Closure $connection_provider
    ) {
    }

    /**
     * @inheritdoc
     */
    public function quote_string(string $string): string
    {
        return $this->connection->quote($string);
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
    public function cast_value(mixed $value, ?string $type = null): int|string|null
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

        /** @var string */
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
}
