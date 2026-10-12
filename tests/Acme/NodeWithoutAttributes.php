<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

class NodeWithoutAttributes extends ActiveRecord
{
    public int $nid;

    public string $title;
}
