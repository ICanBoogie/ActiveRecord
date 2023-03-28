<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord;
use InvalidArgumentException;

/**
 * A transient version of {@link HasManyAssociation}, used during configuration.
 *
 * @internal
 */
final class TransientHasManyAssociation
{
    /**
     * @param class-string<ActiveRecord> $associate
     *     The associate ActiveRecord class or model identifier.
     * @param class-string|non-empty-string|null $through
     *     The pivot ActiveRecord class or model identifier.
     */
    public function __construct(
        public readonly string $associate,
        public readonly ?string $local_key,
        public readonly ?string $foreign_key,
        public readonly ?string $as,
        public readonly ?string $through,
    ) {
        is_a($associate, ActiveRecord::class, true)
        or throw new InvalidArgumentException(
            "Expected the name of a class extending ICanBoogie\ActiveRecord, given: $associate"
        );
    }
}
