<?php

namespace Test\ICanBoogie\Acme\ForeignKey;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * Reviews outlive their book.
 */
class Review extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[BelongsTo(Book::class, null: true, on_delete: OnDelete::SetNull)]
    public ?int $book_id;

    #[Character]
    public string $body;
}
