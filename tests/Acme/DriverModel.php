<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Driver>
 */
#[Model\Record(Driver::class)]
class DriverModel extends Model
{
}
