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
     * @param non-empty-string|non-empty-array<non-empty-string> $columns
     *     Identifiers of the columns making the unique index.
     */
    public function __construct(
        public readonly array|string $columns,
        public readonly bool $unique = false,
        public readonly ?string $name = null
    ) {
    }
}
