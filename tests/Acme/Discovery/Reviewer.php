<?php

namespace Test\ICanBoogie\Acme\Discovery;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Record;

/**
 * Discovered, and configured with {@see Record}.
 */
#[Record(table_name: 'critics', alias: 'critic', connection: 'other')]
class Reviewer extends ActiveRecord
{
    #[Id, Integer]
    public int $id;
}
