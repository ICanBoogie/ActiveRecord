<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Integer;

/**
 * Has a signed, not auto-incremented, primary key, to check the alignment of its children.
 */
class Catalog extends ActiveRecord
{
    #[Id, Integer(size: Integer::SIZE_BIG)]
    public int $catalog_id;
}
