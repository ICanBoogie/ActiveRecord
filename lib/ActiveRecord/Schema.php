<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Schema\SchemaColumn;
use ICanBoogie\ActiveRecord\Schema\Index;
use InvalidArgumentException;

use function array_intersect_key;
use function get_debug_type;
use function sprintf;

/**
 * Schema of a database table.
 */
class Schema
{
    /**
     * @param array{
     *     columns: non-empty-array<non-empty-string, SchemaColumn>,
     *     primary: non-empty-string|non-empty-string[]|null,
     *     indexes: array<Index>,
     *  } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param non-empty-array<non-empty-string, SchemaColumn> $columns
     * @param non-empty-string|non-empty-string[]|null $primary
     * @param array<Index> $indexes
     */
    public function __construct(
        public readonly array $columns,
        public readonly string|array|null $primary = null,
        public readonly array $indexes = []
    ) {
        foreach ($columns as $name => $column) {
            $column instanceof SchemaColumn
                or throw new InvalidArgumentException(
                    sprintf("Expected %s for column %s, given: %s",
                        SchemaColumn::class,
                        $name,
                        get_debug_type($column)
                    )
                );
        }

        foreach ($indexes as $index) {
            $index instanceof Index
                or throw new InvalidArgumentException(
                    sprintf("Expected %s, given: %s",
                        SchemaColumn::class,
                        get_debug_type($index)
                    )
                );
        }
    }

    /**
     * Whether the schema has a column.
     *
     * Prefer using this method than `isset(schema->columns[$name])`.
     */
    public function has_column(string $name): bool
    {
        return isset($this->columns[$name]);
    }

    /**
     * Discards key/value pairs where _key_ is not a column identifier.
     *
     * @param array<non-empty-string, mixed> $values
     *
     * @return array<non-empty-string, mixed>
     */
    public function filter_values(array $values): array
    {
        return array_intersect_key($values, $this->columns);
    }
}
