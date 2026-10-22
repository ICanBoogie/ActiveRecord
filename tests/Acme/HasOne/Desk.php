<?php

namespace Test\ICanBoogie\Acme\HasOne;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

class Desk extends ActiveRecord
{
    #[Id, Serial]
    public int $desk_id;

    #[Character]
    public string $label;
}
