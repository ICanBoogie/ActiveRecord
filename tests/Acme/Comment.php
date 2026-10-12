<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\Text;

class Comment extends ActiveRecord
{
    #[Id, Serial]
    public int $comment_id;

    // 'unsigned' is not specified, it should align on the target
    #[BelongsTo(Article::class, on_delete: OnDelete::Cascade)]
    public int $nid;

    #[Text]
    public string $body;
}
