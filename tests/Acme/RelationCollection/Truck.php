<?php

namespace Test\ICanBoogie\Acme\RelationCollection;

use ICanBoogie\ActiveRecord\Schema\Integer;

/**
 * The relation to the maker is declared on the parent, {@see Vehicle}.
 */
class Truck extends Vehicle
{
    #[Integer]
    public int $payload;
}
