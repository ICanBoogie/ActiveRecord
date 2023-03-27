<?php

namespace ICanBoogie\ActiveRecord\Attribute;

use Attribute;
use ICanBoogie\ActiveRecord\SchemaColumn;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Time implements SchemaAttribute
{
    public function __construct(
        public readonly bool $null = false,
        public readonly ?string $default = null,
    ) {
        SchemaColumn::assert_datetime_default($default);
    }
}
