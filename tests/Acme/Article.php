<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Attribute\Date;
use ICanBoogie\ActiveRecord\Attribute\Index;
use ICanBoogie\ActiveRecord\Attribute\Integer;
use ICanBoogie\ActiveRecord\Attribute\VarChar;

#[Index('rating', name: 'idx_rating')]
class Article extends Node
{
    #[VarChar]
    public string $body;

    #[Date]
    public string $date;

    #[Integer(null: true)]
    public ?int $rating;
}
