<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;

class PersonEquipment extends ActiveRecord
{
    #[Id, BelongsTo(Person::class)]
    public int $person_id;

    #[Id, BelongsTo(Equipment::class)]
    public int $equipment_id;
}
