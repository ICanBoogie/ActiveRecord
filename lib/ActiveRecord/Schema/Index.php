<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

/**
 * An index on one or multiple columns.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class Index implements SchemaAttribute
{
    /**
     * @param array{
     *     columns: non-empty-string|non-empty-array<non-empty-string>,
     *     unique: bool,
     *     name: ?non-empty-string
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param non-empty-string|non-empty-array<non-empty-string> $columns
     *     Identifiers of the columns making the unique index.
     * @param ?non-empty-string $name
     */
    public function __construct(
        public array|string $columns = [],
        public bool $unique = false,
        public ?string $name = null
    ) {
    }
}
