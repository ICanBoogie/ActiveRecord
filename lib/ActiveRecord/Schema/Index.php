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
     *     columns: string|string[],
     *     unique: bool,
     *     name: ?string
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param string|string[] $columns
     *     Identifiers of the columns making the unique index.
     */
    public function __construct(
        public array|string $columns = [],
        public bool $unique = false,
        public ?string $name = null
    ) {
    }
}
