<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\HasMany;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

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
    #[Id, Serial]
    public int $ph_id;

    #[Character]
    public string $name;
}
