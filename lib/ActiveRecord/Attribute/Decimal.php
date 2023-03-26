<?php

namespace ICanBoogie\ActiveRecord\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Decimal extends Column
{
    public function __construct(
        ?int $precision = null,
        bool $null = false,
    ) {
        parent::__construct(
            type: parent::TYPE_DECIMAL,
            size: $precision,
            null: $null,
        );
    }
}
