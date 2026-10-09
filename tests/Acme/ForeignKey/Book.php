<?php

namespace Test\ICanBoogie\Acme\ForeignKey;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * Books are deleted with their author.
 */
class Book extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[BelongsTo(Author::class, on_delete: OnDelete::Cascade)]
    public int $author_id;

    #[Character]
    public string $title;
}
