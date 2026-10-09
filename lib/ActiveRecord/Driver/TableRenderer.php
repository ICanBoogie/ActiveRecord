<?php

namespace ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\Schema\Binary;
use ICanBoogie\ActiveRecord\Schema\Blob;
use ICanBoogie\ActiveRecord\Schema\Boolean;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Column;
use ICanBoogie\ActiveRecord\Schema\Date;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\Schema\Decimal;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Text;
use ICanBoogie\ActiveRecord\Schema\Time;
use ICanBoogie\ActiveRecord\Schema\Timestamp;
use RuntimeException;

use function implode;
use function in_array;
use function is_numeric;
use function str_replace;

abstract class TableRenderer
{
    /**
     * @param string $table_name_prefix
     *     The prefix defined by the connection, used to name the tables referenced by foreign keys.
     */
    public function __construct(
        protected readonly string $table_name_prefix = '',
    ) {
    }

    public function render(Schema $schema, string $prefixed_table_name): string
    {
        $column_defs = implode(",\n", $this->render_column_defs($schema));
        $table_constraints = implode(",\n", $this->render_table_constraints($schema));
        $sep1 = $table_constraints ? ",\n\n" : "\n";
        $sep2 = $table_constraints ? "\n" : '';
        $table_options = implode(" ", $this->render_table_options());
        $sep3 = $table_options ? " " : "";
        $create_index = $this->render_create_index($schema, $prefixed_table_name);
        $sep4 = $create_index ? "\n\n" : '';

        return <<<SQL
        CREATE TABLE $prefixed_table_name (
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
     */
    abstract protected function render_create_index(Schema $schema, string $prefixed_table_name): string;

    /**
     * @return non-empty-string[]
     */
    abstract protected function render_table_options(): array;

    /**
     * Renders the `FOREIGN KEY` table constraints.
     *
     * @return non-empty-string[]
     */
    protected function render_foreign_keys(Schema $schema): array
    {
        $constraints = [];

        foreach ($schema->foreign_keys as $foreign_key) {
            $table = $this->table_name_prefix . $foreign_key->table;

            $constraints[] = "FOREIGN KEY ($foreign_key->column)"
                . " REFERENCES $table ($foreign_key->references)"
                . " ON DELETE {$foreign_key->on_delete->value}";
        }

        return $constraints;
    }

    protected function render_type_name(Column $column): string
    {
        if ($column instanceof Schema\ResolvesToColumn) {
            $column = $column->resolve();
        }

        return match ($column::class) {
            Boolean::class => 'BOOLEAN',

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

    /**
     * Renders the default value of a column.
     *
     * `CURRENT_*` keywords, numeric values of numeric columns, and `TRUE`/`FALSE` of boolean
     * columns are rendered as is, any other value is rendered as a string literal.
     */
    protected function render_default(Column $column): string
    {
        $default = $column->default;

        assert($default !== null);

        if ($this->is_current_keyword($default)) {
            return $default;
        }

        if (($column instanceof Integer || $column instanceof Decimal) && is_numeric($default)) {
            return $default;
        }

        if ($column instanceof Boolean) {
            return $default;
        }

        return $this->quote_string($default);
    }

    /**
     * Whether the default value is one of `CURRENT_TIMESTAMP`, `CURRENT_DATE`, or `CURRENT_TIME`.
     */
    protected function is_current_keyword(string $default): bool
    {
        return in_array($default, [ DateTime::CURRENT_TIMESTAMP, Date::CURRENT_DATE, Time::CURRENT_TIME ], true);
    }

    /**
     * Quotes a string as an SQL string literal.
     */
    protected function quote_string(string $string): string
    {
        return "'" . str_replace("'", "''", $string) . "'";
    }
}
