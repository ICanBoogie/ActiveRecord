<?php

namespace Test\ICanBoogie\Acme\HasOne;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;

/**
 * The pivot between an employee and their desk. The employee is the primary key, so an employee
 * has one desk at most.
 *
 * @property-read Employee $employee
 * @property-read Desk $desk
 */
class DeskAssignment extends ActiveRecord
{
    #[Id, BelongsTo(Employee::class)]
    public int $employee_id;

    #[BelongsTo(Desk::class)]
    public int $desk_id;
}
