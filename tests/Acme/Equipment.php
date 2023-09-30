<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\HasMany;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * @property-read ActiveRecord\Query<Person> $people
 */
#[HasMany(Person::class, through: PersonEquipment::class)]
final class Equipment extends ActiveRecord
{
    #[Id, Serial]
    public int $equipment_id;

    #[Character]
    public string $name;
}
