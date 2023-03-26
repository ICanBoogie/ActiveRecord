<?php

namespace ICanBoogie\ActiveRecord\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Integer implements SchemaAttribute
{
    public function __construct(
        public readonly int|string|null $size = null,
        public readonly bool $unsigned = false,
        public readonly bool $null = false,
        public readonly bool $unique = false,
    ) {
    }
}
