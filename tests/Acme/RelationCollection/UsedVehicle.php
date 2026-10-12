<?php

namespace Test\ICanBoogie\Acme\RelationCollection;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * Belongs twice to {@see Maker}.
 */
class UsedVehicle extends ActiveRecord
{
    #[Id, Serial]
    public int $used_vehicle_id;

    #[BelongsTo(Maker::class)]
    public int $maker_id;

    #[BelongsTo(Maker::class, as: 'previous_maker')]
    public int $previous_maker_id;
}
