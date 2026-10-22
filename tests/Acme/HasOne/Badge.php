<?php

namespace Test\ICanBoogie\Acme\HasOne;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * An employee has one badge: the column that references the employee is unique.
 *
 * @property-read Employee $employee
 */
class Badge extends ActiveRecord
{
    #[Id, Serial]
    public int $badge_id;

    #[BelongsTo(Employee::class, unique: true)]
    public int $employee_id;

    #[Character]
    public string $number;
}
