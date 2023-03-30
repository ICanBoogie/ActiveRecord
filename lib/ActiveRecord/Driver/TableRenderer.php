<?php

namespace ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Binary;
use ICanBoogie\ActiveRecord\Schema\Blob;
use ICanBoogie\ActiveRecord\Schema\Boolean;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Column;

use ICanBoogie\ActiveRecord\Schema\Date;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\Schema\Decimal;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\Text;
use ICanBoogie\ActiveRecord\Schema\Time;
use ICanBoogie\ActiveRecord\Schema\Timestamp;
use RuntimeException;

use function implode;

abstract class TableRenderer
{
    /**
     * @param non-empty-string $prefixed_table_name
     *
     * @return non-empty-string
     */
    public function render(Schema $schema, string $prefixed_table_name): string
    {
        $column_defs = implode(",\n", $this->render_column_defs($schema));
        $table_constraints = implode(",\n", $this->render_table_constraints($schema));
        $sep1 = $table_constraints ? ",\n\n" : "\n";
        $sep2 = $table_constraints ? "\n" : '';
        $table_options = implode(" ", $this->render_table_options($schema));
        $sep3 = $table_options ? " " : "";
        $create_index = $this->render_create_index($schema, $prefixed_table_name);
        $sep4 = $create_index ? "\n\n" : '';

        return <<<SQL
        CREATE TABLE `$prefixed_table_name` (
        $column_defs$sep1$table_constraints$sep2)$sep3$table_options;$sep4$create_index
        SQL;
    }

    /**
     * @return non-empty-string[]
     */
    abstract protected function render_column_defs(Schema $schema): array;

    /**
     * @return non-empty-string[]
     */
    abstract protected function render_table_constraints(Schema $schema): array;

    /**
     * @param Schema $schema
     * @param non-empty-string $prefixed_table_name
     *
     * @return non-empty-string
     */
    abstract protected function render_create_index(Schema $schema, string $prefixed_table_name): string;

    /**
     * @return non-empty-string[]
     */
    abstract protected function render_table_options(): array;

    protected function render_type_name(Column $column): string
    {
        return match ($column::class) {
            Boolean::class => 'BOOLEAN',
            Integer::class => "INTEGER($column->size)",

            Decimal::class => $column->approximate
                ? "FLOAT($column->precision)"
                : "DECIMAL($column->precision, $column->scale)",

            Character::class => $column->fixed
                ? "CHAR($column->size)"
                : "VARCHAR($column->size)",
            Binary::class => $column->fixed
                ? "BINARY($column->size)"
                : "VARBINARY($column->size)",
            Text::class => $column->size === Text::SIZE_REGULAR
                ? "TEXT"
                : "{$column->size}TEXT",
            Blob::class => $column->size === Blob::SIZE_REGULAR
                ? "BLOB"
                : "{$column->size}BLOB",

            DateTime::class => "DATETIME",
            Timestamp::class => "TIMESTAMP",
            Date::class => "DATE",
            Time::class => "TIME",

            default => throw new RuntimeException("Don't know what to do with " . $column::class)
        };
    }
}
