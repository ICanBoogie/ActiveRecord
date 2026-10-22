<?php

namespace ICanBoogie\ActiveRecord\Config;

/**
 * A transient version of {@link Association}, used during configuration.
 *
 * @internal
 */
final readonly class TransientAssociation
{
    /**
     * @param array<TransientHasManyAssociation> $has_many
     * @param array<TransientHasOneAssociation> $has_one
     */
    public function __construct(
        public array $has_many,
        public array $has_one,
    ) {
    }
}
