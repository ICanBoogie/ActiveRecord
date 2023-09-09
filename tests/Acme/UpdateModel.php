<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Update>
 */
class UpdateModel extends Model
{
    public const activerecord_class = Update::class;
}
