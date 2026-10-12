<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * Belongs to a {@see Reader}, with a foreign key, and to a {@see Publication}, without.
 *
 * @property-read Reader $reader
 * @property-read Publication $publication
 */
class Subscription extends ActiveRecord
{
    #[Id, Serial]
    public int $subscription_id;

    #[BelongsTo(Reader::class, on_delete: OnDelete::Cascade)]
    public int $reader_id;

    #[BelongsTo(Publication::class)]
    public int $publication_id;
}
