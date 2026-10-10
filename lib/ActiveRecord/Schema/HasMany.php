<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;
use ICanBoogie\ActiveRecord;

/**
 * Marks a relationship with another model, with the property as reference.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class HasMany implements SchemaAttribute
{
    /**
     * @param class-string<ActiveRecord> $associate
     *     The associate ActiveRecord class.
     * @param non-empty-string|null $foreign_key
     *     The column of the associate that references the owner. Defaults to the associate's
     *     {@see BelongsTo} column that references the owner, or one of its ancestors.
     * @param class-string<ActiveRecord>|null $through
     *     The pivot ActiveRecord class.
     * @param non-empty-string|null $as
     *     The name of the accessor, defaults to the associate model's id.
     */
    public function __construct(
        public string $associate,
        public ?string $foreign_key = null,
        public ?string $through = null,
        public ?string $as = null,
    ) {
    }
}
