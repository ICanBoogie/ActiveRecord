<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

/**
 * @extends ActiveRecord<int>
 */
class Update extends ActiveRecord
{
    public int $update_id;
    public int $subscriber_id;
    public string $updated_at;
    public string $update_hash;
}
