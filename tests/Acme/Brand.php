<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

class Brand extends ActiveRecord
{
    public int $brand_id;
    public string $name;
}
