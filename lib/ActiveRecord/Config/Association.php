<?php

namespace ICanBoogie\ActiveRecord\Config;

/**
 * @internal
 */
final class Association
{
    /**
     * @param array<BelongsToAssociation> $belongs_to
     * @param array<HasManyAssociation> $has_many
     */
    public function __construct(
        public readonly array $belongs_to,
        public readonly array $has_many,
    ) {
    }
}
