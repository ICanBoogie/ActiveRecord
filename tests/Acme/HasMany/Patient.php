<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Attribute\Id;
use ICanBoogie\ActiveRecord\Attribute\Serial;
use ICanBoogie\ActiveRecord\Attribute\VarChar;
use ICanBoogie\ActiveRecord\Query;

/**
 * @property-read Query<Physician> $physicians
 * @property-read Query<Appointment> $appointments
 */
class Patient extends ActiveRecord
{
    #[Serial]
    #[Id]
    public int $pa_id;

    #[VarChar]
    public string $name;
}
