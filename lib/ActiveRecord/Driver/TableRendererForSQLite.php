<?php

namespace ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Column;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Serial;

use function implode;
use function is_array;

/**
 * @see https://www.sqlite.org/lang_createtable.html
 */
final class TableRendererForSQLite extends TableRenderer
{
    protected function render_column_defs(Schema $schema): array
    {
        $render = [];

        foreach ($schema->columns as $name => $column) {
            $type = $this->render_type_name($column);
            $constraint = $this->render_column_constraint($column);

            $render[] = "$name $type $constraint";
        }

        return $render;
    }

    protected function render_type_name(Column $column): string
    {
        return match ($column::class) {
            Serial::class, BelongsTo::class => "INTEGER", // SQLite doesn't like sizes on SERIAL
            Integer::class => "INTEGER($column->size)",

            default => parent::render_type_name($column)
        };
    }

    private function render_column_constraint(Column $column): string
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

        $constraint .= $column->null ? " NULL" : " NOT NULL";
        $constraint .= $column->default !== null ? " DEFAULT $column->default" : '';
        $constraint .= $column->unique ? " UNIQUE" : '';
        $constraint .= $column->collate ? " COLLATE $column->collate" : '';

        // foreign-key-clause goes here

        return ltrim($constraint);
    }

    protected function render_table_constraints(Schema $schema): array
    {
        $constraints = [];

        //
        // PRIMARY KEY
        //
        $primary = $schema->primary;

        if (is_array($primary)) {
            $primary = implode(', ', $primary);
            $constraints[] = "PRIMARY KEY ($primary)";
        } elseif (is_string($primary) && !$schema->columns[$primary] instanceof Serial) {
            $constraints[] = "PRIMARY KEY ($primary)";
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
            $constraints[] = "UNIQUE ($indexed_columns)";
        }

        return $constraints;
    }

    protected function render_create_index(Schema $schema, string $prefixed_table_name): string
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

    protected function render_table_options(): array
    {
        return [];
    }
}
