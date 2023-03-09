<?php

namespace ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord;

/**
 * @property-read Physician $physician
 * @property-read Patient $patient
 */
class Appointment extends ActiveRecord
{
    public int $ap_id;
    public int $physician_id;
    public int $patient_id;
    public string $appointment_date;
}
