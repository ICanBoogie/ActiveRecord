<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Location>
 */
#[Model\Record(Location::class)]
class LocationModel extends Model
{
}
