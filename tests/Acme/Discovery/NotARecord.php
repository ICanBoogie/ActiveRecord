<?php

namespace Test\ICanBoogie\Acme\Discovery;

use ICanBoogie\ActiveRecord\Schema\Integer;

/**
 * Not discovered, it is not an active record.
 */
class NotARecord
{
    #[Integer]
    public int $id;
}
