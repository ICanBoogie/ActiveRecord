<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

/**
 * @extends ActiveRecord<int>
 */
class Node extends ActiveRecord
{
    public int $nid;
    public string $title;
}
