<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Attribute\BelongsTo;
use ICanBoogie\ActiveRecord\Attribute\Binary;
use ICanBoogie\ActiveRecord\Attribute\DateTime;
use ICanBoogie\ActiveRecord\Attribute\Id;
use ICanBoogie\ActiveRecord\Attribute\Serial;

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
