<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * The root of a record hierarchy, with a big primary key.
 */
class Publication extends ActiveRecord
{
    #[Id, Serial(size: Integer::SIZE_BIG)]
    public int $publication_id;

    #[Character]
    public string $title;
}
