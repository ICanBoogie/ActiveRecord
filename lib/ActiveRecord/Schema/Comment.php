<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Comment implements ColumnExtraAttribute
{
    public function __construct(
        public readonly string $comment
    ) {
    }
}
