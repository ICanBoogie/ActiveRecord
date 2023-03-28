<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

/**
 * An index on one or multiple columns.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Index implements SchemaAttribute
{
    /**
     * @param array{
     *     columns: non-empty-string|non-empty-array<non-empty-string>,
     *     unique: bool,
     *     name: ?non-empty-string
     * } $an_array
     *
     * @return object
     */
    public static function __set_state(array $an_array): object
    {
        return new self(...$an_array);
    }

    /**
     * @param non-empty-string|non-empty-array<non-empty-string> $columns
     *     Identifiers of the columns making the unique index.
     * @param ?non-empty-string $name
     */
    public function __construct(
        public readonly array|string $columns,
        public readonly bool $unique = false,
        public readonly ?string $name = null
    ) {
    }
}
