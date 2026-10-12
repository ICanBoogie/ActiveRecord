<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Integer;

/**
 * Has a primary key made of several columns.
 */
class Seat extends ActiveRecord
{
    #[Id, Integer]
    public int $row;

    #[Id, Integer]
    public int $number;
}
