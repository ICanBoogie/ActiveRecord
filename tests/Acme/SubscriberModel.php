<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Subscriber>
 */
class SubscriberModel extends Model
{
    public const activerecord_class = Subscriber::class;
}
