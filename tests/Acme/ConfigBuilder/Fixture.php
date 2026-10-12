<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * Belongs twice to {@see Team}.
 */
class Fixture extends ActiveRecord
{
    #[Id, Serial]
    public int $fixture_id;

    #[BelongsTo(Team::class)]
    public int $home_team_id;

    #[BelongsTo(Team::class)]
    public int $away_team_id;
}
