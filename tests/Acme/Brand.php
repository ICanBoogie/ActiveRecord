<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

class Brand extends ActiveRecord
{
    #[Id, Serial]
    public int $brand_id;

    #[Character]
    public string $name;
}
