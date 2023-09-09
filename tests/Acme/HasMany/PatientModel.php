<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord\Model;
use Test\ICanBoogie\Acme\HasMany\Physician;

/**
 * @extends Model<int, Patient>
 */
class PatientModel extends Model
{
    protected static string $activerecord_class = Patient::class;
}
