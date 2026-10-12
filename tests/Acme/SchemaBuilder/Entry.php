<?php

namespace Test\ICanBoogie\Acme\SchemaBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Index;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\Text;

#[Index('title', name: 'idx_title')]
class Entry extends ActiveRecord
{
    #[Id, Serial]
    public int $entry_id;

    #[BelongsTo(Writer::class, on_delete: OnDelete::Cascade)]
    public int $writer_id;

    #[Character]
    public string $title;

    #[Text(null: true)]
    public ?string $body;
}
