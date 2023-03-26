<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

class Count extends ActiveRecord
{
    public int $id;
    public string $name;
    public string $date;
}
