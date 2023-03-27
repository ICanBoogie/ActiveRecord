<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * @property-read Physician $physician
 * @property-read Patient $patient
 *
 * @extends ActiveRecord<int>
 */
class Appointment extends ActiveRecord
{
    #[Id, Serial]
    public int $ap_id;

    #[BelongsTo(Physician::class)]
    public int $physician_id;

    #[BelongsTo(Patient::class)]
    public int $patient_id;

    #[DateTime]
    public string $appointment_date;
}
