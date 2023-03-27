<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;
use ICanBoogie\ActiveRecord\SchemaColumn;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class DateTime implements ColumnAttribute
{
    public function __construct(
        public readonly bool $null = false,
        public readonly ?string $default = null,
    ) {
        SchemaColumn::assert_datetime_default($default);
    }
}
