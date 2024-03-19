<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord;

/**
 * A _belong to_ association between two models.
 *
 * @internal
 */
final readonly class BelongsToAssociation
{
    /**
     * @param array{
     *     associate: class-string<ActiveRecord>,
     *     local_key: non-empty-string,
     *     foreign_key: non-empty-string,
     *     as: non-empty-string,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param class-string<ActiveRecord> $associate
     * @param non-empty-string $local_key
     * @param non-empty-string $foreign_key
     * @param non-empty-string $as
     */
    public function __construct(
        public string $associate,
        public string $local_key,
        public string $foreign_key,
        public string $as,
    ) {
    }
}
