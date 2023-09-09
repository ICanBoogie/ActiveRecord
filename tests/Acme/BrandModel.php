<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Brand>
 */
#[Model\Record(Brand::class)]
class BrandModel extends Model
{
}
