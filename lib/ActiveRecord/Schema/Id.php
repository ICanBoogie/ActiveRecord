<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

/**
 * Marks one or multiple properties that constitute the record identifier
 * i.e. the primary key in the database.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Id implements ColumnExtraAttribute
{
}