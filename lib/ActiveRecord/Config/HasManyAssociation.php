<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord;

/**
 * An _has many_ association between two models.
 *
 * @internal
 */
final class HasManyAssociation
{
    /**
     * @param array{
     *     associate: class-string<ActiveRecord>,
     *     foreign_key: non-empty-string,
     *     as: non-empty-string,
     *     through: ?class-string<ActiveRecord>,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param class-string<ActiveRecord> $associate
     * @param non-empty-string $foreign_key
     * @param non-empty-string $as
     * @param class-string<ActiveRecord>|null $through
     */
    public function __construct(
        public readonly string $associate,
        public readonly string $foreign_key,
        public readonly string $as,
        public readonly string|null $through,
    ) {
    }
}
