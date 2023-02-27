<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

class Article extends ActiveRecord
{
    public int $article_id;
    public string $title;
}
