<?php

namespace Test\ICanBoogie\Acme\HasMany;

use ICanBoogie\ActiveRecord\Model;
use Test\ICanBoogie\Acme\HasMany\Physician;

/**
 * @extends Model<int, Patient>
 */
#[Model\Record(Patient::class)]
class PatientModel extends Model
{
}
