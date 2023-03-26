<?php

namespace ICanBoogie\ActiveRecord\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class VarChar implements SchemaAttribute
{
    public function __construct(
        public readonly int $size = 255,
        public readonly bool $null = false,
        public readonly bool $unique = false,
        public readonly ?string $collate = null,
    ) {
    }
}
