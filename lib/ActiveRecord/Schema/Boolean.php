<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Boolean extends Integer
{
    public function __construct(
        bool $null = false,
    ) {
        parent::__construct(
            size: parent::SIZE_TINY,
            unsigned: true,
            null: $null,
        );
    }
}
