<?php

namespace Test\ICanBoogie\Acme\ForeignKey;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * Forms a cycle with {@see Egg}: the foreign keys of both tables reference each other.
 */
class Chicken extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[BelongsTo(Egg::class, null: true, on_delete: OnDelete::SetNull)]
    public ?int $egg_id;
}
