<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

/**
 * @extends ActiveRecord<int>
 */
class Node extends ActiveRecord
{
    #[ActiveRecord\Schema\Serial]
    #[ActiveRecord\Schema\Id]
    public int $nid;

    #[ActiveRecord\Schema\VarChar]
    public string $title;
}
