<?php

namespace Test\ICanBoogie\ActiveRecordTest;

use ICanBoogie\ActiveRecord;

/**
 * Record with a non-serial (natural) primary key.
 */
final class Natural extends ActiveRecord
{
    public string $code;

    public string $name;
}
