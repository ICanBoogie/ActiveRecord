<?php

namespace Test\ICanBoogie\Acme\ForeignKey;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * A book on loan cannot be deleted.
 */
class Loan extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[BelongsTo(Book::class, on_delete: OnDelete::Restrict)]
    public int $book_id;

    #[Character]
    public string $borrower;
}
