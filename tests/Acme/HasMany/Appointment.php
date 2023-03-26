<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Attribute\BelongsTo;
use ICanBoogie\ActiveRecord\Attribute\DateTime;
use ICanBoogie\ActiveRecord\Attribute\Id;
use ICanBoogie\ActiveRecord\Attribute\Serial;

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
