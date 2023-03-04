<?php

namespace ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Query;

/**
 * @property-read Query<Patient> $patients
 * @property-read Query<Appointment> $appointments
 */
class Physician extends ActiveRecord
{
    public int $ph_id;
    public string $name;
}
