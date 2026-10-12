<?php

namespace Test\ICanBoogie\Acme\SchemaBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Id;

/**
 * A record with a primary key that is not a column.
 */
class PrimaryWithoutColumn extends ActiveRecord
{
    #[Id]
    public int $id;
}
