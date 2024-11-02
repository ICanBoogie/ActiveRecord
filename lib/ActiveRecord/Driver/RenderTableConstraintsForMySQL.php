<?php

namespace ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Schema;

trait RenderTableConstraintsForMySQL
{
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
        } elseif (is_string($primary)) {
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
}
