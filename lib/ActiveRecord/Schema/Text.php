<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Text implements ColumnAttribute
{
    public function __construct(
        public readonly string|null $size = null,
        public readonly bool $null = false,
        public readonly bool $unique = false,
        public readonly ?string $collate = null,
    ) {
    }
}
