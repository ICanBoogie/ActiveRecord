<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord\Model;
use Test\ICanBoogie\Acme\HasMany\Physician;

/**
 * @extends Model<int, Patient>
 */
class PatientModel extends Model
{
    public const activerecord_class = Patient::class;
}
