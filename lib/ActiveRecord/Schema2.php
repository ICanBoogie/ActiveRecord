<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Schema\ColumnAttribute;
use ICanBoogie\ActiveRecord\Schema\Index;
use InvalidArgumentException;

use function get_debug_type;
use function sprintf;

class Schema2
{
    /**
     * @param non-empty-array<non-empty-string, ColumnAttribute> $columns
     * @param array<Index> $indexes
     */
    public function __construct(
        public array $columns,
        public string|array|null $primary = null,
        public array $indexes = [],
    ) {
        foreach ($columns as $id => $column) {
            $id !== '' or
                throw new InvalidArgumentException("The column name cannot be empty");

            $column instanceof ColumnAttribute
                or throw new InvalidArgumentException(sprintf(
                    "Columns should be instances of %s, given: %s",
                    ColumnAttribute::class,
                    get_debug_type($column)
                ));
        }
    }
}
