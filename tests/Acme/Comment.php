<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

/**
 * @extends ActiveRecord<int>
 */
class Comment extends ActiveRecord
{
    public int $comment_id;
    public int $nid;
    public string $body;
}
