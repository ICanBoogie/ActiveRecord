<?php

namespace Test\ICanBoogie\Acme\Discovery;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Character;

/**
 * Discovered, its properties are tagged.
 */
class Author extends ActiveRecord
{
    #[Id, Integer]
    public int $id;

    #[Character(size: 80)]
    public string $name;
}
