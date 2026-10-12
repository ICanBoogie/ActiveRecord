<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\HasMany;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * @property-read ActiveRecord\Query<Subscription> $subscriptions
 * @property-read ActiveRecord\Query<Publication> $publications
 */
#[HasMany(Subscription::class)]
#[HasMany(Publication::class, through: Subscription::class)]
class Reader extends ActiveRecord
{
    #[Id, Serial]
    public int $reader_id;

    #[Character]
    public string $name;
}
