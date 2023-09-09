<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Update>
 */
class UpdateModel extends Model
{
    protected static string $activerecord_class = Update::class;
}
