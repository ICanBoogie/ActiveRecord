<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Attribute\Id;
use ICanBoogie\ActiveRecord\Attribute\Serial;
use ICanBoogie\ActiveRecord\Attribute\VarChar;
use ICanBoogie\ActiveRecord\Query;

/**
 * @property-read Query<Patient> $patients
 * @property-read Query<Appointment> $appointments
 */
class Physician extends ActiveRecord
{
    #[Serial]
    #[Id]
    public int $ph_id;

    #[VarChar]
    public string $name;
}
