<?php

namespace ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Boolean;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\ColumnAttribute;
use ICanBoogie\ActiveRecord\Schema\Constraints;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\Text;
use ICanBoogie\ActiveRecord\Schema2;
use RuntimeException;

use function implode;
use function is_array;

/**
 * @see https://www.sqlite.org/lang_createtable.html
 */
class TableRendererForSQLite
{
    public function render(Schema2 $schema, string $prefixed_table_name): string
    {
        $column_defs = implode(",\n", $this->render_column_defs($schema));
        $table_constraints = $this->render_table_constraints($schema);
        $sep1 = $table_constraints ? ",\n\n" : "\n";
        $create_index = $this->render_create_index($schema, $prefixed_table_name);
        $sep2 = $create_index ? "\n\n" : '';

        return <<<SQL
        CREATE TABLE $prefixed_table_name (
        $column_defs$sep1$table_constraints);$sep2$create_index
        SQL;
    }

    /**
     * @return string[]
     */
    private function render_column_defs(Schema2 $schema): array
    {
        $render = [];

        foreach ($schema->columns as $name => $column) {
            $type = $this->render_type_name($column);
            $constraint = $this->render_column_constraint($column);

            $render[] = "$name $type $constraint";
        }

        return $render;
    }

    private function render_type_name(ColumnAttribute $column): string
    {
        return match ($column::class) {
            Boolean::class => 'BOOLEAN',
            Serial::class, BelongsTo::class => "INTEGER",
            Integer::class => "INTEGER($column->size)",
            Character::class => match ($column->fixed) {
                true => "CHAR($column->size)",
                false => "VARCHAR($column->size)"
            },
            Text::class => "{$column->size}TEXT",

            default => throw new RuntimeException("Don't know what to do with " . $column::class)
        };
    }

    private function render_column_constraint(ColumnAttribute $column): string
    {
        $constraint = '';

        //
        // AUTOINCREMENT can only be used with PRIMARY KEY,
        // but there can be only one PRIMARY KEY,
        // careful with table-constraint.
        //
        if ($column instanceof Serial) {
            $constraint .= " PRIMARY KEY AUTOINCREMENT";
        }

        if ($column instanceof Constraints) {
            $constraint .= $column->null ? " NULL" : " NOT NULL";
            $constraint .= $column->default !== null ? " DEFAULT $column->default" : '';
            $constraint .= $column->unique ? " UNIQUE" : '';
            $constraint .= $column->collate ? " COLLATE $column->collate" : '';
        }

        // foreign-key-clause goes here

        return ltrim($constraint);
    }

    private function render_table_constraints(Schema2 $schema): string
    {
        $constraints = '';

        //
        // PRIMARY KEY
        //
        $primary = $schema->primary;

        if (is_array($primary)) {
            $primary = implode(', ', $primary);
        }

        if ($primary && !$schema->columns[$primary] instanceof Serial) {
            $constraints .= "PRIMARY KEY ($primary)\n";
        }

        //
        // UNIQUE
        //
        foreach ($schema->indexes as $index) {
            if (!$index->unique || $index->name) {
                continue;
            }

            $indexed_columns = is_array($index->columns)
                ? implode(', ', $index->columns)
                : $index->columns;
            $constraints .= "UNIQUE ($indexed_columns)\n";
        }

        return $constraints;
    }

    private function render_create_index(Schema2 $schema, string $prefixed_table_name): string
    {
        $create_index = '';

        foreach ($schema->indexes as $index) {
            $name = $index->name;

            // Unnamed UNIQUE indexes have been added during render_table_constraints()
            if ($index->unique && !$name) {
                continue;
            }

            $unique = $index->unique ? 'UNIQUE ' : '';
            $columns = $index->columns;
            if (!$name) {
                $name = is_array($columns) ? implode('_', $columns) : $columns;
            }
            if (is_array($columns)) {
                $columns = implode(', ', $columns);
            }
            $create_index .= "CREATE {$unique}INDEX $name ON $prefixed_table_name ($columns);\n";
        }

        return rtrim($create_index, "\n");
    }
}
