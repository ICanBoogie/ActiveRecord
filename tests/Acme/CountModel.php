<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Count>
 */
#[Model\Record(Count::class)]
class CountModel extends Model
{
}
