<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Car>
 */
class CarModel extends Model
{
    public const activerecord_class = Car::class;
}
