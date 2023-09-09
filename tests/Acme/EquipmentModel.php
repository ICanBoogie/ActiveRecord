<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Equipment>
 */
class EquipmentModel extends Model
{
    protected static string $activerecord_class = Equipment::class;
}
