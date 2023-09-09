<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Appointment>
 */
class AppointmentModel extends Model
{
    protected static string $activerecord_class = Appointment::class;
}
