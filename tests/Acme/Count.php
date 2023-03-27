<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Date;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * @extends ActiveRecord<int>
 */
class Count extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[Character]
    public string $name;

    #[Date]
    public string $date;
}
