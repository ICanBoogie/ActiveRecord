<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * @property Brand $brand
 * @property Driver $driver
 *
 * @extends ActiveRecord<int>
 */
class Car extends ActiveRecord
{
    #[Id, Serial]
    public int $car_id;

    #[BelongsTo(Driver::class)]
    public int $driver_id;

    #[BelongsTo(Brand::class)]
    public int $brand_id;

    #[Character]
    public string $name;
}
