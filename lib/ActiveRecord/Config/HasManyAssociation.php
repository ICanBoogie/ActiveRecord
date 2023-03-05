<?php

namespace ICanBoogie\ActiveRecord\Config;

/**
 * An _has many_ association between two models.
 *
 * @internal
 */
final class HasManyAssociation
{
    public function __construct(
        public readonly string $model_id,
        public readonly string $local_key,
        public readonly string $foreign_key,
        public readonly string $as,
        public readonly string|null $through,
    ) {
    }
}
