<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * Belongs to a {@see Publication} and to a {@see Magazine}, which extends it.
 */
class Award extends ActiveRecord
{
    #[Id, Serial]
    public int $award_id;

    #[BelongsTo(Publication::class)]
    public int $publication_id;

    #[BelongsTo(Magazine::class)]
    public int $magazine_id;
}
