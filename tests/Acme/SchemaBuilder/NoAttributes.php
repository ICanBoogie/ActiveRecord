<?php

namespace Test\ICanBoogie\Acme\SchemaBuilder;

use ICanBoogie\ActiveRecord;

/**
 * A record without schema attributes.
 */
class NoAttributes extends ActiveRecord
{
    public int $id;
}
