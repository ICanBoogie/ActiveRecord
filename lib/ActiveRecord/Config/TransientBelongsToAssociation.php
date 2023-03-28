<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord;

final class TransientBelongsToAssociation
{
    /**
     * @param class-string<ActiveRecord> $associate
     *     The associate ActiveRecord class.
     */
    public function __construct(
        public readonly string $associate,
        public readonly ?string $local_key,
        public readonly ?string $as,
    ) {
    }
}
