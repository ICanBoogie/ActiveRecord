<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Decimal implements ColumnAttribute
{
    public function __construct(
        public readonly ?int $precision = null,
        public readonly bool $null = false,
    ) {
    }
}
