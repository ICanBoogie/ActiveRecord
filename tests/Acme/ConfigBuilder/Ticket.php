<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * Belongs to a {@see Seat}, which has no single column primary key.
 *
 * @property-read Seat $seat
 */
class Ticket extends ActiveRecord
{
    #[Id, Serial]
    public int $ticket_id;

    #[BelongsTo(Seat::class)]
    public int $seat_id;
}
