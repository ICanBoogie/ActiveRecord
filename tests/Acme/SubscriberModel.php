<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Subscriber>
 */
class SubscriberModel extends Model
{
    protected static string $activerecord_class = Subscriber::class;
}
