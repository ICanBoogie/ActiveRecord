<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Appointment>
 */
class AppointmentModel extends Model
{
    public const activerecord_class = Appointment::class;
}
