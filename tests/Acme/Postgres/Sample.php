<?php

namespace Test\ICanBoogie\Acme\Postgres;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Boolean;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Serial;

class Sample extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[Character]
    public string $name;

    #[Boolean]
    public bool $active;

    #[Integer]
    public int $count;
}
