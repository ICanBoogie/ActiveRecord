<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Binary implements ColumnAttribute
{
    public function __construct(
        public readonly int $size = 255,
        public readonly bool $null = false,
        public readonly bool $unique = false,
    ) {
    }
}
