<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\VarChar;

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
