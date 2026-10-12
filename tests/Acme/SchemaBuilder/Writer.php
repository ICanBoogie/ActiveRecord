<?php

namespace Test\ICanBoogie\Acme\SchemaBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

class Writer extends ActiveRecord
{
    #[Id, Serial]
    public int $writer_id;

    #[Character(size: 64)]
    public string $name;
}
