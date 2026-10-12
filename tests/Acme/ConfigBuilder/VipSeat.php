<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord\Schema\Character;

/**
 * Cannot extend {@see Seat}, its primary key is not a single column.
 */
class VipSeat extends Seat
{
    #[Character]
    public string $lounge;
}
