<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

class Comment extends ActiveRecord
{
    public int $comment_id;
    public int $article_id;
    public string $body;
}
