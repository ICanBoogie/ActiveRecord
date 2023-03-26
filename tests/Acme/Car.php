<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Attribute\BelongsTo;
use ICanBoogie\ActiveRecord\Attribute\Id;
use ICanBoogie\ActiveRecord\Attribute\Serial;
use ICanBoogie\ActiveRecord\Attribute\VarChar;

/**
 * @property Brand $brand
 * @property Driver $driver
 *
 * @extends ActiveRecord<int>
 */
class Car extends ActiveRecord
{
    #[Serial]
    #[Id]
    public int $car_id;

    #[BelongsTo(Driver::class)]
    public int $driver_id;

    #[BelongsTo(Brand::class)]
    public int $brand_id;

    #[VarChar]
    public string $name;
}
