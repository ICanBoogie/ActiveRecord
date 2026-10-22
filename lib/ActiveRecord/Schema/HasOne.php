<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;
use ICanBoogie\ActiveRecord;

/**
 * Marks a relationship with one other record, which references the owner, directly or through a pivot.
 *
 * The relation returns the first matching record, or `null`. That there is at most one is up to the schema, e.g. with a
 * unique key on the column that references the owner.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class HasOne implements SchemaAttribute
{
    /**
     * @param class-string<ActiveRecord> $associate
     *     The associate ActiveRecord class.
     * @param non-empty-string|null $foreign_key
     *     The column of the associate that references the owner, or of the pivot with `$through`.
     *     Defaults to its {@see BelongsTo} column that references the owner, or one of its ancestors.
     * @param class-string<ActiveRecord>|null $through
     *     The pivot ActiveRecord class.
     * @param non-empty-string|null $as
     *     The name of the accessor, defaults to the associate model's alias.
     */
    public function __construct(
        public string $associate,
        public ?string $foreign_key = null,
        public ?string $through = null,
        public ?string $as = null,
    ) {
    }
}
