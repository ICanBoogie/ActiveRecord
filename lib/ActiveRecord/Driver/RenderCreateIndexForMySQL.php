<?php

namespace ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Schema;

trait RenderCreateIndexForMySQL
{
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
            $columns = $this->render_column_list($columns);
            $create_index .= "CREATE {$unique}INDEX $name ON $prefixed_table_name ($columns);\n";
        }

        /** @var non-empty-string */
        return rtrim($create_index, "\n");
    }

    /**
     * @param string|string[] $columns
     */
    private function render_column_list(string|array $columns): string
    {
        return is_array($columns) ? implode(', ', $columns) : $columns;
    }
}
