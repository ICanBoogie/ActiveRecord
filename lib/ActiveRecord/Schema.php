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

use InvalidArgumentException;

use function array_intersect_key;
use function count;
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
     *     indexes: array<SchemaIndex>,
     *  } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self($an_array['columns'], $an_array['indexes']);
    }

    /**
     * @var non-empty-string|non-empty-string[]|null
     */
    public readonly string|array|null $primary;

    /**
     * @param non-empty-array<non-empty-string, SchemaColumn> $columns
     * @param array<SchemaIndex> $indexes
     */
    public function __construct(
        public readonly array $columns,
        public readonly array $indexes = []
    ) {
        $primary = [];

        foreach ($columns as $name => $column) {
            $column instanceof SchemaColumn
                or throw new InvalidArgumentException(
                    sprintf("Expected %s, given: %s",
                        SchemaColumn::class,
                        get_debug_type($column)
                    )
                );

            if ($column->primary) {
                $primary[] = $name;
            }
        }

        $this->primary = match (count($primary)) {
            0 => null,
            1 => $primary[0],
            default => $primary,
        };
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
