<?php

namespace ICanBoogie\ActiveRecord\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Blob extends Column
{
    public function __construct(
        int $size = 255,
        bool $null = false,
        bool $unique = false,
    ) {
        parent::__construct(
            type: parent::TYPE_BLOB,
            size: $size,
            null: $null,
            unique: $unique,
        );
    }
}
