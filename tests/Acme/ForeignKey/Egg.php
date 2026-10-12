<?php

namespace Test\ICanBoogie\Acme\ForeignKey;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * Forms a cycle with {@see Chicken}.
 */
class Egg extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[BelongsTo(Chicken::class, on_delete: OnDelete::Cascade)]
    public int $chicken_id;
}
