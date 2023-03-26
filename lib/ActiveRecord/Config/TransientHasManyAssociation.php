<?php

namespace ICanBoogie\ActiveRecord\Config;

/**
 * A transient version of {@link HasManyAssociation}, used during configuration.
 *
 * @internal
 */
final class TransientHasManyAssociation
{
    /**
     * @param class-string|non-empty-string $associate
     *     The associate ActiveRecord class or model identifier.
     * @param class-string|non-empty-string|null $through
     *     The pivot ActiveRecord class or model identifier.
     */
    public function __construct(
        public readonly string $associate,
        public readonly string|null $local_key,
        public readonly string|null $foreign_key,
        public readonly string|null $as,
        public readonly string|null $through,
    ) {
    }
}
