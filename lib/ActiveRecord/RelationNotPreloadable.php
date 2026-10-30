<?php

namespace ICanBoogie\ActiveRecord;

use LogicException;
use Throwable;

/**
 * Exception thrown when asking to preload a relation that cannot be preloaded.
 *
 * It is a limit of the library, not bad data: fix the code that builds the query, for example by
 * leaving the relation out of {@see Query::includes()}.
 */
class RelationNotPreloadable extends LogicException implements Exception
{
    public function __construct(
        public readonly Relation $relation,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            "The relation `$relation->as` of {$relation->owner->activerecord_class} cannot be preloaded.",
            previous: $previous
        );
    }
}
