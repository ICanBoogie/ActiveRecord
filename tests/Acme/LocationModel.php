<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Location>
 */
class LocationModel extends Model
{
    protected static string $activerecord_class = Location::class;
}
