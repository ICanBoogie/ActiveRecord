<?php

namespace ICanBoogie\ActiveRecord\ModelTest;

use ICanBoogie\ActiveRecord;

/**
 * @property Brand $brand
 * @property Driver $driver
 */
class Car extends ActiveRecord
{
	public $driver_id;
	public $brand_id;
}
