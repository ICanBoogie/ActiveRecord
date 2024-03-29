<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\ActiveRecordCache;

use ICanBoogie\ActiveRecord\ActiveRecordCache;
use ICanBoogie\ActiveRecord\Model;

/**
 * Abstract root class for an active records cache.
 */
abstract class AbstractActiveRecordCache implements ActiveRecordCache
{
    public function __construct(
        protected Model $model
    ) {
    }
}
