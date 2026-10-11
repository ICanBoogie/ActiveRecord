<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use LogicException;
use Throwable;

use function count;

/**
 * Exception thrown when more than one relation links two models.
 */
class AmbiguousRelation extends LogicException implements Exception
{
    /**
     * @param Model<ActiveRecord> $model
     * @param Model<ActiveRecord> $other
     * @param Relation[] $relations
     *     The relations linking the models.
     */
    public function __construct(
        public readonly Model $model,
        public readonly Model $other,
        public readonly array $relations,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            count($relations) . " relations link $model->activerecord_class to $other->activerecord_class.",
            previous: $previous
        );
    }
}
