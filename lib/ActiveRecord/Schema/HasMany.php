<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;
use ICanBoogie\ActiveRecord;

/**
 * Marks a relationship with another model, with the property as reference.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class HasMany implements SchemaAttribute
{
    /**
     * @param class-string<ActiveRecord> $associate
     *     The associate ActiveRecord class.
     * @param non-empty-string|null $foreign_key
     *      Column key on the associate model, defaults to the local primary key (which might be wrong).
     * @param class-string<ActiveRecord>|null $through
     *     The pivot ActiveRecord class.
     * @param non-empty-string|null $as
     *     The name of the accessor, defaults to the associate model's id.
     */
    public function __construct(
        public readonly string $associate,
        public readonly ?string $foreign_key = null,
        public readonly ?string $through = null,
        public readonly ?string $as = null,
    ) {
    }
}
