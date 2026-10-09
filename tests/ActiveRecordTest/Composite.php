<?php

namespace Test\ICanBoogie\ActiveRecordTest;

use ICanBoogie\ActiveRecord;

/**
 * Record with a multi-column primary key.
 */
final class Composite extends ActiveRecord
{
    public int $a;

    public int $b;

    public string $name;
}
