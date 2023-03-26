<?php

namespace ICanBoogie\ActiveRecord\Attribute;

use Attribute;

/**
 * An index on one or multiple columns.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Index implements SchemaAttribute
{
    /**
     * @param string|array<string> $columns
     *     Identifiers of the columns making the unique index.
     */
    public function __construct(
        public readonly array|string $columns,
        public readonly bool $unique = false,
        public readonly ?string $name = null
    ) {
    }
}
