<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;

class Node extends ActiveRecord
{
    public int $nid;
    public string $title;
}
