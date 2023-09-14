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

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Column;
use ICanBoogie\ActiveRecord\Schema\Index;
use InvalidArgumentException;

use function array_intersect_key;
use function get_debug_type;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Schema of a database table.
 */
class Schema
{
    /**
     * @param array{
     *     columns: non-empty-array<non-empty-string, Column>,
     *     primary: non-empty-string|non-empty-array<non-empty-string>|null,
     *     indexes: array<Index>,
     *  } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        unset($an_array['has_single_column_primary']); // @phpstan-ignore-line
        unset($an_array['has_multi_column_primary']); // @phpstan-ignore-line

        return new self(...$an_array);
    }

    /**
     * @param class-string<ActiveRecord> $activerecord_class
     */
    public static function from(string $activerecord_class): self
    {
        return (new SchemaBuilder())
            ->use_record($activerecord_class)
            ->build();
    }

    public readonly bool $has_single_column_primary;
    public readonly bool $has_multi_column_primary;

    /**
     * @param non-empty-array<non-empty-string, Column> $columns
     * @param non-empty-string|non-empty-array<non-empty-string>|null $primary
     * @param array<Index> $indexes
     */
    public function __construct(
        public readonly array $columns,
        public readonly string|array|null $primary = null,
        public readonly array $indexes = []
    ) {
        foreach ($columns as $name => $column) {
            $column instanceof Column
                or throw new InvalidArgumentException(
                    sprintf("Expected %s for column %s, given: %s",
                        Column::class,
                        $name,
                        get_debug_type($column)
                    )
                );
        }

        foreach ($indexes as $index) {
            $index instanceof Index
                or throw new InvalidArgumentException(
                    sprintf("Expected %s, given: %s",
                        Index::class,
                        get_debug_type($index)
                    )
                );
        }

        $this->has_single_column_primary = is_string($this->primary);
        $this->has_multi_column_primary = is_array($this->primary);
    }

    /**
     * Whether the schema has a column.
     *
     * Prefer using this method than `isset(schema->columns[$name])`.
     *
     * @param non-empty-string $name
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

    /**
     * @return iterable<non-empty-string, BelongsTo>
     *     Where _key_ is the name of a column.
     */
    public function belongs_to_iterator(): iterable
    {
        foreach ($this->columns as $name => $column) {
            if ($column instanceof BelongsTo) {
                yield $name => $column;
            }
        }
    }
}
