<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use LogicException;
use Throwable;

/**
 * Exception thrown when no relation links two models.
 */
class RelationNotFound extends LogicException implements Exception
{
    /**
     * @param Model<ActiveRecord> $model
     * @param Model<ActiveRecord> $other
     */
    public function __construct(
        public readonly Model $model,
        public readonly Model $other,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            "No relation links $model->activerecord_class to $other->activerecord_class.",
            previous: $previous
        );
    }
}
