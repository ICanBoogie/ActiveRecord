<?php

namespace ICanBoogie\ActiveRecord\Config;

/**
 * A transient version of {@link HasManyAssociation}, used during configuration.
 *
 * @internal
 */
final class TransientHasManyAssociation
{
    public function __construct(
        public readonly string $model_id,
        public readonly string|null $local_key,
        public readonly string|null $foreign_key,
        public readonly string|null $as,
        public readonly string|null $through,
    ) {
    }
}
