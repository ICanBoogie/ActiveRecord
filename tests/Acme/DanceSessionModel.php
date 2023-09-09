<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, DanceSession>
 */
class DanceSessionModel extends Model
{
    protected static string $activerecord_class = DanceSession::class;
}
