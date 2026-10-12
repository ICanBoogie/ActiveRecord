<?php

namespace Test\ICanBoogie\Acme\RelationCollection;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * The primary key is `id` while the records referencing it use `maker_id`.
 */
class Maker extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[Character]
    public string $name;
}
