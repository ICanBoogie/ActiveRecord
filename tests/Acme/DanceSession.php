<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\HasMany;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * @property-read ActiveRecord\Query<Person> $people
 *
 * @extends ActiveRecord<int>
 */
#[HasMany(Person::class)]
final class DanceSession extends ActiveRecord
{
    #[Id, Serial]
    public int $dance_session_id;

    #[Character]
    public string $name;
}
