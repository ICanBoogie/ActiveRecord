<?php

namespace Test\ICanBoogie\Acme\ForeignKey;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

class Author extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[Character]
    public string $name;
}
