<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Physician>
 */
#[Model\Record(Physician::class)]
class PhysicianModel extends Model
{
}
