<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Brand>
 */
class BrandModel extends Model
{
    protected static string $activerecord_class = Brand::class;
}
