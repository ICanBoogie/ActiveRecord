<?php

namespace ICanBoogie\ActiveRecord\ActiveRecordCache;

use ICanBoogie\ActiveRecord;
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
