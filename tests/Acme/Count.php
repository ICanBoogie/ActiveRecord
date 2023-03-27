<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Date;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\VarChar;

/**
 * @extends ActiveRecord<int>
 */
class Count extends ActiveRecord
{
    #[Serial]
    #[Id]
    public int $id;

    #[VarChar]
    public string $name;

    #[Date]
    public string $date;
}
