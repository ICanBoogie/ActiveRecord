<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;

/**
 * Has a primary key that is not an integer.
 */
class Tag extends ActiveRecord
{
    #[Id, Character(size: 16)]
    public string $code;
}
