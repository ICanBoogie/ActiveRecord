<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Comment implements ColumnExtraAttribute
{
    public function __construct(
        public string $comment
    ) {
    }
}
