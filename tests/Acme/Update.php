<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Binary;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * @extends ActiveRecord<int>
 */
class Update extends ActiveRecord
{
    #[Serial]
    #[Id]
    public int $update_id;

    #[BelongsTo(Subscriber::class)]
    public int $subscriber_id;

    #[DateTime]
    public string $updated_at;

    #[Binary(32)]
    public string $update_hash;
}
