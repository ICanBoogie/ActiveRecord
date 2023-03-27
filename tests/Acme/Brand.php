<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\VarChar;

/**
 * @extends ActiveRecord<int>
 */
class Brand extends ActiveRecord
{
    #[Serial]
    #[Id]
    public int $brand_id;

    #[VarChar]
    public string $name;
}
