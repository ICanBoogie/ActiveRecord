<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Brand>
 */
class BrandModel extends Model
{
    public const activerecord_class = Brand::class;
}
