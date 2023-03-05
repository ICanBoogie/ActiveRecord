<?php

namespace ICanBoogie\ActiveRecord\Config;

/**
 * A _belong to_ association between two models.
 *
 * @internal
 */
final class BelongsToAssociation
{
    public function __construct(
        public readonly string $model_id,
        public readonly string $local_key,
        public readonly string $foreign_key,
        public readonly string $as,
    ) {
    }
}
