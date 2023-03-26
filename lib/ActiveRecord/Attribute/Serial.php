<?php

namespace ICanBoogie\ActiveRecord\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Serial implements SchemaAttribute
{
    public function __construct()
    {
    }
}
