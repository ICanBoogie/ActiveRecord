<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Attribute\Date;
use ICanBoogie\ActiveRecord\Attribute\Id;
use ICanBoogie\ActiveRecord\Attribute\Serial;
use ICanBoogie\ActiveRecord\Attribute\VarChar;

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
