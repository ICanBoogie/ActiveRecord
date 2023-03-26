<?php

namespace ICanBoogie\ActiveRecord\Attribute;

use Attribute;
use ICanBoogie\ActiveRecord\SchemaColumn;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Time extends Column
{
    public function __construct(
        bool $null = false,
        ?string $default = null,
    ) {
        SchemaColumn::assert_datetime_default($default);

        parent::__construct(
            type: parent::TYPE_TIME,
            null: $null,
            default: $default,
        );
    }
}
