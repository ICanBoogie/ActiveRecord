<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord;

/**
 * An _has one_ association between two models.
 *
 * @internal
 */
final readonly class HasOneAssociation
{
    /**
     * @param array{
     *     associate: class-string<ActiveRecord>,
     *     foreign_key: non-empty-string,
     *     as: non-empty-string,
     *     through: ?class-string<ActiveRecord>,
     *     through_key: ?non-empty-string,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param class-string<ActiveRecord> $associate
     * @param non-empty-string $foreign_key
     *     The column that references the owner: on the associate, or on the pivot with `$through`.
     * @param non-empty-string $as
     * @param class-string<ActiveRecord>|null $through
     * @param non-empty-string|null $through_key
     *     The column of the pivot that references the associate, with `$through`.
     */
    public function __construct(
        public string $associate,
        public string $foreign_key,
        public string $as,
        public string|null $through,
        public string|null $through_key,
    ) {
    }
}
