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
     */
    public function __construct(
        public array $has_many,
    ) {
    }
}
