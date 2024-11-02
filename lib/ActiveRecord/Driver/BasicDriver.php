<?php

namespace ICanBoogie\ActiveRecord\Driver;

use DateTimeInterface;
use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\ActiveRecord\Connection;
use ICanBoogie\ActiveRecord\Driver;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\DateTime;

/**
 * Basic connection driver.
 *
 * @property-read Connection $connection {@see self::get_connection}
 */
abstract class BasicDriver implements Driver
{
    /**
     * @see get_connection
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
    public function cast_value(mixed $value, string $type = null): int|string|null
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
