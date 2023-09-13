<?php

namespace ICanBoogie\ActiveRecord\Config;

/**
 * A transient version of {@link Association}, used during configuration.
 *
 * @internal
 */
final class TransientAssociation
{
    /**
     * @param array<TransientHasManyAssociation> $has_many
     */
    public function __construct(
        public readonly array $has_many,
    ) {
    }
}
