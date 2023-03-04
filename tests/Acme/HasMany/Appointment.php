<?php

namespace ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord;

class Appointment extends ActiveRecord
{
    public int $ap_id;
    public int $physician_id;
    public int $patient_id;
    public string $appointment_date;
}
