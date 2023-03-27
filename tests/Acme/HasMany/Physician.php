<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\HasMany;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\VarChar;
use ICanBoogie\ActiveRecord\Query;

/**
 * @property-read Query<Appointment> $appointments
 * @property-read Query<Patient> $patients
 *
 * @extends ActiveRecord<int>
 */
#[HasMany(Appointment::class, foreign_key: 'physician_id')]
#[HasMany(Patient::class, through: Appointment::class)]
class Physician extends ActiveRecord
{
    #[Serial]
    #[Id]
    public int $ph_id;

    #[VarChar]
    public string $name;
}
