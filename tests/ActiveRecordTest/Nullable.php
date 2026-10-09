<?php

namespace Test\ICanBoogie\ActiveRecordTest;

use ICanBoogie\ActiveRecord;

/**
 * Record with a nullable column and a non-nullable column with a default value.
 */
final class Nullable extends ActiveRecord
{
    public int $id;

    public ?string $name = null;

    public ?string $nickname = null;
}
