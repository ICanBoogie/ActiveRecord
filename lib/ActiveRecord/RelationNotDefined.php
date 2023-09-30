<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\OffsetNotDefined;
use Throwable;

/**
 * Exception thrown in an attempt to get a relation that is not defined.
 */
class RelationNotDefined extends OffsetNotDefined implements Exception
{
    /**
     * @param non-empty-string $relation_name
     *     Name of the undefined relation.
     */
    public function __construct(
        public readonly string $relation_name,
        public readonly RelationCollection $collection,
        ?Throwable $previous = null
    ) {
        parent::__construct(offset: $relation_name, container: $collection, previous: $previous);
    }
}
