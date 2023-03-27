<?php

namespace ICanBoogie\ActiveRecord\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Decimal implements SchemaAttribute
{
    public function __construct(
        public readonly ?int $precision = null,
        public readonly bool $null = false,
    ) {
    }
}
