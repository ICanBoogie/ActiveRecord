<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Attribute\Id;
use ICanBoogie\ActiveRecord\Attribute\Serial;
use ICanBoogie\ActiveRecord\Attribute\VarChar;

/**
 * @extends ActiveRecord<int>
 */
class Subscriber extends ActiveRecord
{
    #[Serial]
    #[Id]
    public int $subscriber_id;

    #[VarChar]
    public string $email;
}
