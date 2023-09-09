<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Subscriber>
 */
#[Model\Record(Subscriber::class)]
class SubscriberModel extends Model
{
}
