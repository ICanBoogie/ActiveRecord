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

use ArrayAccess;
use ArrayIterator;
use ICanBoogie\Accessor\AccessorTrait;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use Traversable;

use function array_intersect_key;
use function array_keys;
use function count;
use function get_debug_type;
use function implode;
use function is_string;
use function reset;
use function sprintf;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * Representation of a database table schema.
 *
 * @property-read array<string, SchemaColumn> $columns The columns of the schema.
 * @property-read SchemaIndex[] $indexes The indexes of the schema.
 * @property-read array $unique_indexes The unique indexes of the schema.
 * @property-read string[]|string|null $primary The primary key of the schema. A multi-dimensional
 * primary key is returned as an array.
 *
 * @implements ArrayAccess<string, SchemaColumn>
 * @implements IteratorAggregate<string, SchemaColumn>
 */
class Schema implements ArrayAccess, IteratorAggregate
{
    /**
     * @uses get_columns
     * @uses get_primary
     * @uses get_indexes
     * @uses get_unique_indexes
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
        return new self($an_array['columns'], $an_array['indexes']);
    }

    /**
     * @return array<string, SchemaColumn>
     */
    private function get_columns(): array
    {
        return $this->columns;
    }

    /**
     * @return SchemaIndex[]
     */
    private function get_indexes(): array
    {
        return $this->indexes;
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
        private array $columns = [],
        private array $indexes = []
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

    private function set_column(string $column_id, SchemaColumn $column): void
    {
        trigger_error("the schema is becoming readonly", E_USER_DEPRECATED);

        $this->columns[$column_id] = $column;
    }

    /**
     * Checks if a column exists.
     *
     * @param string $offset A column identifier.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->columns[$offset]);
    }

    /**
     * Returns a column.
     *
     * @param string $offset A column identifier.
     */
    public function offsetGet($offset): SchemaColumn
    {
        return $this->columns[$offset];
    }

    /**
     * Adds a column to the schema.
     *
     * @param string $offset A column identifier.
     * @param SchemaColumn $value
     *
     * @deprecated
     */
    public function offsetSet($offset, $value): void
    {
        trigger_error("the schema is becoming readonly", E_USER_DEPRECATED);

        $this->set_column($offset, $value);
    }

    /**
     * Removes a column from the schema.
     *
     * @param string $offset A column identifier.
     *
     * @deprecated
     */
    public function offsetUnset($offset): void
    {
        trigger_error("the schema is becoming readonly", E_USER_DEPRECATED);

        unset($this->columns[$offset]);
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
