<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

/**
 * Marks a relationship with another model, with the property as reference.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class BelongsTo implements ColumnAttribute
{
    /**
     * @param class-string $associate
     *     The associate ActiveRecord class.
     * @param non-empty-string|null $as
     *     The name of the prototype property used to access the association, default is built from property name.
     */
    public function __construct(
        public readonly string $associate,
        public readonly bool $null = false,
        public readonly bool $unique = false,
        public readonly ?string $as = null,
    ) {
    }
}
