<?php

namespace Test\ICanBoogie\Acme\RelationCollection;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

class Vehicle extends ActiveRecord
{
    #[Id, Serial]
    public int $vehicle_id;

    #[BelongsTo(Maker::class)]
    public int $maker_id;
}
