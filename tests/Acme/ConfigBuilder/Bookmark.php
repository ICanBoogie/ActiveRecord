<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * Uses OnDelete::SetNull on a column that is not nullable.
 */
class Bookmark extends ActiveRecord
{
    #[Id, Serial]
    public int $bookmark_id;

    #[BelongsTo(Team::class, on_delete: OnDelete::SetNull)]
    public int $team_id;
}
