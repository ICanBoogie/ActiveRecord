<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord\Schema\Integer;

/**
 * Extends {@see Publication}, it shares its primary key.
 */
class Magazine extends Publication
{
    #[Integer]
    public int $issue;
}
