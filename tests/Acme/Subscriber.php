<?php

namespace ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

class Subscriber extends ActiveRecord
{
    public int $subscriber_id;
    public string $email;
}
