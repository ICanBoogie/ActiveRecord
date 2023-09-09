<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Count>
 */
class CountModel extends Model
{
    protected static string $activerecord_class = Count::class;
}
