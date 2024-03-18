<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Schema\Date;
use ICanBoogie\ActiveRecord\Schema\Index;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Text;

class Article extends Node
{
    #[Text]
    public string $body;

    #[Date]
    public string $date;

    #[Integer(null: true)]
    #[Index(name: 'idx_rating')]
    public ?int $rating;
}
