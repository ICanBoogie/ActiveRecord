<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\OffsetNotDefined;
use Throwable;

/**
 * Exception thrown in an attempt to get a relation that is not defined.
 */
class RelationNotDefined extends OffsetNotDefined implements Exception
{
    public function __construct(
        public readonly string $relation_name,
        public readonly RelationCollection $collection,
        ?Throwable $previous = null
    ) {
        parent::__construct(offset: $relation_name, container: $collection, previous: $previous);
    }
}
