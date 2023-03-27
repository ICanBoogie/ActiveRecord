<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Date;
use ICanBoogie\ActiveRecord\Schema\Index;
use ICanBoogie\ActiveRecord\Schema\Integer;

#[Index('rating', name: 'idx_rating')]
class Article extends Node
{
    #[Character]
    public string $body;

    #[Date]
    public string $date;

    #[Integer(null: true)]
    public ?int $rating;
}
