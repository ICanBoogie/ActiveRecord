<?php

namespace ICanBoogie\ActiveRecord\Config;

/**
 * A _belong to_ association between two models.
 *
 * @internal
 */
final class BelongsToAssociation
{
    /**
     * @param array{
     *     associate: string,
     *     local_key: string,
     *     foreign_key: string,
     *     as: string,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param string $associate
     *     A model identifier.
     */
    public function __construct(
        public readonly string $associate,
        public readonly string $local_key,
        public readonly string $foreign_key,
        public readonly string $as,
    ) {
    }
}
