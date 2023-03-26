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
     */
    public function __construct(
        public readonly string $active_record_class,
        public readonly bool $null = false,
        public readonly bool $unique = false,
    ) {
    }
}
