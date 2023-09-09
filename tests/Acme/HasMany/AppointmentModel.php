<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Appointment>
 */
#[Model\Record(Appointment::class)]
class AppointmentModel extends Model
{
}
