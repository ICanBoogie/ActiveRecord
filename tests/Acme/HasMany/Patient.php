<?php

namespace ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Query;

/**
 * @property-read Query<Physician> $physicians
 * @property-read Query<Appointment> $appointments
 */
class Patient extends ActiveRecord
{
    public int $pa_id;
    public string $name;
}
