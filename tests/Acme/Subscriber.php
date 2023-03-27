<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * @extends ActiveRecord<int>
 */
class Subscriber extends ActiveRecord
{
    #[Id, Serial]
    public int $subscriber_id;

    #[Character]
    public string $email;
}
