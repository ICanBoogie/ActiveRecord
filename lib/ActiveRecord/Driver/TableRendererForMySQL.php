<?php

namespace ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Column;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Serial;

use function str_replace;

/**
 * @link https://www.sqlite.org/lang_createtable.html
 */
final class TableRendererForMySQL extends TableRenderer
{
    use RenderCreateIndexForMySQL;
    use RenderTableConstraintsForMySQL;

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
            Serial::class, BelongsTo::class, Integer::class => "INTEGER($column->size)",

            default => parent::render_type_name($column)
        };
    }

    private function render_column_constraint(Column $column): string
    {
        $constraint = '';

        if ($column instanceof Integer) {
            $constraint .= $column->unsigned ? " UNSIGNED" : '';
        }

        $constraint .= $column->null ? " NULL" : " NOT NULL";

        if ($column instanceof Serial) {
            $constraint .= " AUTO_INCREMENT";
        }

        $constraint .= $column->default !== null ? " DEFAULT " . $this->render_default($column) : '';
        $constraint .= $column->unique ? " UNIQUE" : '';
        $constraint .= $column->collate ? " COLLATE $column->collate" : '';

        // foreign-key-clause goes here

        return ltrim($constraint);
    }

    protected function render_default(Column $column): string
    {
        $default = parent::render_default($column);

        return $this->is_current_keyword($default) ? "($default)" : $default;
    }

    /**
     * MySQL also treats backslashes as escape characters in string literals.
     */
    protected function quote_string(string $string): string
    {
        return parent::quote_string(str_replace('\\', '\\\\', $string));
    }

    protected function render_table_options(): array
    {
        $options = [];
        $options[] = "COLLATE utf8_general_ci";

        return $options;
    }
}
