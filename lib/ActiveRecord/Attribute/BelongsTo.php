<?php

namespace ICanBoogie\ActiveRecord\Attribute;

use Attribute;

/**
 * Marks a relationship with another model, with the property as reference.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class BelongsTo implements SchemaAttribute
{
    /**
     * @param class-string $active_record_class
     * @param string|null $as
     *     The name of the prototype property used to access the association, default is built from property name.
     */
    public function __construct(
        public readonly string $active_record_class,
        public readonly bool $null = false,
        public readonly bool $unique = false,
        public readonly ?string $as = null,
    ) {
    }
}
