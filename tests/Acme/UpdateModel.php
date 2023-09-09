<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Update>
 */
#[Model\Record(Update::class)]
class UpdateModel extends Model
{
}
