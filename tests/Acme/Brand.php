<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Attribute\Id;
use ICanBoogie\ActiveRecord\Attribute\Serial;
use ICanBoogie\ActiveRecord\Attribute\VarChar;

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
