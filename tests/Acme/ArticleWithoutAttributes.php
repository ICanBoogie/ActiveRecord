<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Query;

/**
 * @property-read Query<Comment> $comments
 */
class ArticleWithoutAttributes extends NodeWithoutAttributes
{
    public string $body;

    public string $date;

    public ?int $rating;
}
