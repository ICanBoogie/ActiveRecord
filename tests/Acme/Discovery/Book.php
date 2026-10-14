<?php

namespace Test\ICanBoogie\Acme\Discovery;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Integer;

/**
 * Discovered, and refers to {@see Author}.
 */
class Book extends ActiveRecord
{
    #[Id, Integer]
    public int $id;

    #[BelongsTo(Author::class)]
    public int $author_id;
}
