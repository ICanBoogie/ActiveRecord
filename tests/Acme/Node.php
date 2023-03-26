<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

/**
 * @extends ActiveRecord<int>
 */
class Node extends ActiveRecord
{
    #[ActiveRecord\Attribute\Serial]
    #[ActiveRecord\Attribute\Id]
    public int $nid;

    #[ActiveRecord\Attribute\VarChar]
    public string $title;
}
