<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Attribute\BelongsTo;
use ICanBoogie\ActiveRecord\Attribute\Id;
use ICanBoogie\ActiveRecord\Attribute\Serial;
use ICanBoogie\ActiveRecord\Attribute\Text;

/**
 * @extends ActiveRecord<int>
 */
class Comment extends ActiveRecord
{
    #[Serial]
    #[Id]
    public int $comment_id;

    #[BelongsTo(Node::class)]
    public int $nid;

    #[Text]
    public string $body;
}
