<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

/**
 * @property Brand $brand
 * @property Driver $driver
 */
class Car extends ActiveRecord
{
    public int $driver_id;
    public int $brand_id;
}
