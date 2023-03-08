<?php

namespace ICanBoogie\ActiveRecord\Config;

/**
 * An _has many_ association between two models.
 *
 * @internal
 */
final class HasManyAssociation
{
    /**
     * @param array{
     *     model_id: string,
     *     local_key: string,
     *     foreign_key: string,
     *     as: string,
     *     through: ?string,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    public function __construct(
        public readonly string $model_id,
        public readonly string $local_key,
        public readonly string $foreign_key,
        public readonly string $as,
        public readonly string|null $through,
    ) {
    }
}
