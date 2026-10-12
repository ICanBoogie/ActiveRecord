<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\Schema\Index;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Text;

/**
 * @property-read Query<Comment> $comments
 */
#[Index('rating', name: 'idx_rating')]
class Article extends Node
{
    #[Text]
    public string $body;

    #[DateTime(default: DateTime::CURRENT_TIMESTAMP)]
    public string $date;

    #[Integer(null: true)]
    public ?int $rating;
}
