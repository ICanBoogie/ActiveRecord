<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * References a {@see Tag} with a foreign key.
 *
 * @property-read Tag $tag
 */
class Tagging extends ActiveRecord
{
    #[Id, Serial]
    public int $tagging_id;

    #[BelongsTo(Tag::class, on_delete: OnDelete::Cascade)]
    public int $tag_id;
}
