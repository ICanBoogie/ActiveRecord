<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord\Model;

/**
 * An _has many_ association between two models.
 *
 * @internal
 */
final class HasManyAssociation
{
    /**
     * @param array{
     *     associate: class-string<Model>,
     *     local_key: string,
     *     foreign_key: string,
     *     as: string,
     *     through: ?class-string<Model>,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param class-string<Model> $associate
     * @param class-string<Model>|null $through
     */
    public function __construct(
        public readonly string $associate,
        public readonly string $local_key,
        public readonly string $foreign_key,
        public readonly string $as,
        public readonly string|null $through,
    ) {
    }
}
