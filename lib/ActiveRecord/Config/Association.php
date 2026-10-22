<?php

namespace ICanBoogie\ActiveRecord\Config;

/**
 * @internal
 */
final readonly class Association
{
    /**
     * @param array{
     *     belongs_to: array<BelongsToAssociation>,
     *     has_many: array<HasManyAssociation>,
     *     has_one: array<HasOneAssociation>,
     * } $an_array
     *
     * @return self
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param array<BelongsToAssociation> $belongs_to
     * @param array<HasManyAssociation> $has_many
     * @param array<HasOneAssociation> $has_one
     *     Defaults to none, so that a config exported before has-one existed can still be loaded.
     */
    public function __construct(
        public array $belongs_to,
        public array $has_many,
        public array $has_one = [],
    ) {
    }
}
