<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\HasMany;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * @property-read Query<Physician> $physicians
 * @property-read Query<Appointment> $appointments
 *
 * @extends ActiveRecord<int>
 */
#[HasMany(Appointment::class, foreign_key: 'patient_id')]
#[HasMany(Physician::class, through: Appointment::class)]
class Patient extends ActiveRecord
{
    #[Id, Serial]
    public int $pa_id;

    #[Character]
    public string $name;
}
