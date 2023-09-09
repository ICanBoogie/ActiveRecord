<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Car>
 */
#[Model\Record(Car::class)]
class CarModel extends Model
{
}
