<?php

namespace Test\ICanBoogie\Acme\HasOne;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\HasOne;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * @property-read Badge|null $badge
 * @property-read Desk|null $desk
 */
#[HasOne(Badge::class)]
#[HasOne(Desk::class, through: DeskAssignment::class)]
class Employee extends ActiveRecord
{
    #[Id, Serial]
    public int $employee_id;

    #[Character]
    public string $name;
}
