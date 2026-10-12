<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\Text;

/**
 * References a {@see Magazine}, which is a child table.
 */
class Letter extends ActiveRecord
{
    #[Id, Serial]
    public int $letter_id;

    #[BelongsTo(Magazine::class, on_delete: OnDelete::Cascade)]
    public int $magazine_id;

    #[Text]
    public string $body;
}
