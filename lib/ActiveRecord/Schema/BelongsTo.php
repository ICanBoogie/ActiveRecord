<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

/**
 * Marks a relationship with another model, with the property as reference.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class BelongsTo extends Constraints implements ColumnAttribute
{
    /**
     * @param class-string $associate
     *     The associate ActiveRecord class.
     * @param non-empty-string|null $as
     *     The name of prototype getter for the association, by default it is build according to the column name e.g.
     *    `article_id` would result is a `article` getter.
     */
    public function __construct(
        public readonly string $associate,
        bool $null = false,
        bool $unique = false,
        public readonly ?string $as = null,
    ) {
        parent::__construct(
            null: $null,
            unique: $unique,
        );
    }
}
