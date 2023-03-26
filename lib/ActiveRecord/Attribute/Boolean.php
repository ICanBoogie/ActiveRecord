<?php

namespace ICanBoogie\ActiveRecord\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Boolean implements SchemaAttribute
{
    public function __construct(
        public readonly bool $null = false,
    ) {
    }
}
