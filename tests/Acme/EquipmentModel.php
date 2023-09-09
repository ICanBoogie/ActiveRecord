<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Equipment>
 */
#[Model\Record(Equipment::class)]
class EquipmentModel extends Model
{
}
