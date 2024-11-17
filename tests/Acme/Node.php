<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

class Node extends ActiveRecord
{
    #[Id, Serial]
    public int $nid;

    #[Character]
    public string $title;
}
