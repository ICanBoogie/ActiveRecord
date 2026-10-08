<?php

namespace ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Schema;

/**
 * Connection driver for PostgreSQL.
 */
final class PostgreSQLDriver extends BasicDriver
{
    /**
     * @inheritDoc
     */
    public function quote_identifier(string $identifier): string
    {
        return "\"$identifier\"";
    }

    /**
     * @inheritDoc
     */
    public function cast_value(mixed $value, ?string $type = null): int|string|null
    {
        if ($value === false) {
            return 'false';
        }

        if ($value === true) {
            return 'true';
        }

        return parent::cast_value($value, $type);
    }

    /**
     * @inheritDoc
     */
    protected function render_create_table(string $table_name, Schema $schema): string
    {
        return new TableRendererForPostgreSQL()
            ->render($schema, $table_name);
    }

    /**
     * @inheritdoc
     */
    public function table_exists(string $table_name): bool
    {
        $tables = $this->connection
            ->query('SELECT tablename FROM pg_tables WHERE schemaname = current_schema()')
            ->all(\PDO::FETCH_COLUMN);

        return \in_array($table_name, $tables);
    }

    /**
     * @inheritdoc
     */
    public function optimize(): void
    {
        $this->connection->exec('VACUUM ANALYZE');
    }
}
