<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * Has no relation to {@see Team}.
 */
class Venue extends ActiveRecord
{
    #[Id, Serial]
    public int $venue_id;

    #[Character]
    public string $name;
}
