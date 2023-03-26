<?php

namespace ICanBoogie\ActiveRecord\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Text implements SchemaAttribute
{
    public function __construct(
        public readonly string|null $size = null,
        public readonly bool $null = false,
        public readonly bool $unique = false,
        public readonly ?string $collate = null,
    ) {
    }
}
