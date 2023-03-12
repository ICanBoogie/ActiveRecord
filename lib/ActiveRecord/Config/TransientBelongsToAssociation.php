<?php

namespace ICanBoogie\ActiveRecord\Config;

final class TransientBelongsToAssociation
{
    /**
     * @param string $associate
     *     A model identifier.
     */
    public function __construct(
        public readonly string $associate,
        public readonly string|null $local_key,
        public readonly string|null $as,
    ) {
    }
}
