<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord;

/**
 * A transient version of {@see HasOneAssociation}, used during configuration.
 *
 * @internal
 */
final readonly class TransientHasOneAssociation
{
    /**
     * @param class-string<ActiveRecord> $associate
     *     The associate ActiveRecord class.
     * @param class-string<ActiveRecord>|null $through
     *     An optional pivot ActiveRecord class.
     */
    public function __construct(
        public string $associate,
        public ?string $foreign_key,
        public ?string $as,
        public ?string $through,
    ) {
    }
}
