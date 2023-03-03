<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

class Driver extends ActiveRecord
{
    public int $driver_id;
    public string $name;
}
