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
use IteratorAggregate;
use LogicException;

use Traversable;

use function array_intersect_key;
use function array_keys;
use function count;
use function implode;
use function is_string;
use function reset;

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
     * @param array{ 'columns': array<string, SchemaColumn> } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self($an_array['columns']);
    }

    /**
     * @var array<string, SchemaColumn>
     */
    private array $columns = [];

    /**
     * @return array<string, SchemaColumn>
     */
    private function get_columns(): array
    {
        return $this->columns;
    }

    /**
     * @var SchemaIndex[]
     */
    private array $indexes = [];

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
     */
    public function __construct(array $columns = [])
    {
        foreach ($columns as $column_id => $column) {
            $this[$column_id] = $column;
        }
    }

    private function set_column(string $column_id, SchemaColumn $column): void
    {
        $this->columns[$column_id] = $column;
    }

    /**
     * @var array<int, string[]>
     */
    private array $unique_indexes = [];

    /**
     * Create an index on one of multiple columns.
     *
     * @param array<string> $columns Identifiers of the columns making the unique index.
     *
     * @return $this
     */
    public function index(
        array|string $columns,
        bool $unique = false,
        ?string $name = null
    ): self {
        if (is_string($columns)) {
            $columns = [ $columns ];
        }

        foreach ($columns as $column_id) {
            if (!isset($this->columns[$column_id])) {
                $defined = implode(', ', array_keys($this->columns));

                throw new LogicException(
                    "Unable to create UNIQUE constraint, column '$column_id' is not defined."
                    . " Defined columns are: $defined."
                );
            }
        }

        $this->indexes[] = new SchemaIndex($columns, unique: $unique, name: $name);

        return $this;
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
     */
    public function offsetSet($offset, $value): void
    {
        $this->set_column($offset, $value);
    }

    /**
     * Removes a column from the schema.
     *
     * @param string $offset A column identifier.
     */
    public function offsetUnset($offset): void
    {
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
