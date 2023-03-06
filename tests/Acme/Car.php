<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

/**
 * @property Brand $brand
 * @property Driver $driver
 */
class Car extends ActiveRecord
{
    public int $car_id;
    public int $driver_id;
    public int $brand_id;
    public string $name;
}
