<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Driver>
 */
class DriverModel extends Model
{
    protected static string $activerecord_class = Driver::class;
}
