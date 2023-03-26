<?php

namespace ICanBoogie\ActiveRecord\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Comment
{
    public function __construct(
        public readonly string $comment
    ) {
    }
}
