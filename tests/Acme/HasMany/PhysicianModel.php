<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Physician>
 */
class PhysicianModel extends Model
{
    protected static string $activerecord_class = Physician::class;
}
