<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

/**
 * An index on one or multiple columns.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Index implements SchemaAttribute
{
    /**
     * @param array{
     *     columns: non-empty-string|non-empty-list<non-empty-string>,
     *     unique: bool,
     *     name: ?non-empty-string
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param non-empty-string|non-empty-list<non-empty-string> $columns
     * @param non-empty-string|null $name
     *     Identifiers of the columns making the unique index.
     */
    public function __construct(
        public string|array $columns,
        public bool $unique = false,
        public ?string $name = null
    ) {
    }
}
