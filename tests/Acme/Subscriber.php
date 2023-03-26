<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

/**
 * @extends ActiveRecord<int>
 */
class Subscriber extends ActiveRecord
{
    public int $subscriber_id;
    public string $email;
}
