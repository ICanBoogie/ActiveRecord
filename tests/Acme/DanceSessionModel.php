<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, DanceSession>
 */
#[Model\Record(DanceSession::class)]
class DanceSessionModel extends Model
{
}
