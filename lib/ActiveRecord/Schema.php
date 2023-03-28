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

use ArrayIterator;
use ICanBoogie\Accessor\AccessorTrait;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

use function array_intersect_key;
use function count;
use function get_debug_type;
use function reset;
use function sprintf;

/**
 * Representation of a database table schema.
 *
 * @property-read string[]|string|null $primary The primary key of the schema. A multi-dimensional
 * primary key is returned as an array.
 *
 * @implements IteratorAggregate<string, SchemaColumn>
 */
class Schema implements IteratorAggregate
{
    /**
     * @uses get_primary
     */
    use AccessorTrait;

    /**
     * @param array{
     *     columns: array<string, SchemaColumn>,
     *     indexes: array<SchemaIndex>,
     *  } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @return string|string[]|null
     */
    private function get_primary(): string|array|null
    {
        $primary = [];

        foreach ($this->columns as $column_id => $column) {
            if (!$column->primary) {
                continue;
            }

            $primary[] = $column_id;
        }

        return match (count($primary)) {
            0 => null,
            1 => reset($primary),
            default => $primary,
        };
    }

    /**
     * @param array<string, SchemaColumn> $columns
     * @param array<SchemaIndex> $indexes
     */
    public function __construct(
        public readonly array $columns = [],
        public readonly array $indexes = []
    ) {
        foreach ($columns as $column) {
            $column instanceof SchemaColumn
                or throw new InvalidArgumentException(
                    sprintf("Expected %s, given: %s",
                        SchemaColumn::class,
                        get_debug_type($column)
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
     * @return Traversable<string, SchemaColumn>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->columns);
    }

    /**
     * Discards key/value pairs where _key_ is not a column identifier.
     *
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    public function filter_values(array $values): array
    {
        return array_intersect_key($values, $this->columns);
    }
}
