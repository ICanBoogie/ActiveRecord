<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\Text;

/**
 * @extends ActiveRecord<int>
 */
class Comment extends ActiveRecord
{
    #[Id, Serial]
    public int $comment_id;

    #[BelongsTo(Node::class)]
    public int $nid;

    #[Text]
    public string $body;
}
