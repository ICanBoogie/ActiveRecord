<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Car>
 */
class CarModel extends Model
{
    protected static string $activerecord_class = Car::class;
}
