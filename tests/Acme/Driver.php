<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Attribute\Id;
use ICanBoogie\ActiveRecord\Attribute\Serial;
use ICanBoogie\ActiveRecord\Attribute\VarChar;

/**
 * @extends ActiveRecord<int>
 */
class Driver extends ActiveRecord
{
    #[Serial]
    #[Id]
    public int $driver_id;

    #[VarChar]
    public string $name;
}
