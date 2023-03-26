<?php

namespace ICanBoogie\ActiveRecord\Config;

final class TransientBelongsToAssociation
{
    /**
     * @param class-string|string $associate
     *     A model class or identifier.
     */
    public function __construct(
        public readonly string $associate,
        public readonly string|null $local_key,
        public readonly string|null $as,
    ) {
    }
}
