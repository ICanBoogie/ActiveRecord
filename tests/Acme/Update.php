<?php

namespace ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

class Update extends ActiveRecord
{
    public int $update_id;
    public int $subscriber_id;
    public string $updated_at;
    public string $update_hash;
}