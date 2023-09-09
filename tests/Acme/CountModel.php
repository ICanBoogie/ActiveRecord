<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Count>
 */
class CountModel extends Model
{
    public const activerecord_class = Count::class;
}
