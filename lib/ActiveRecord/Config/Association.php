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
     * } $an_array
     *
     * @return static
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param array<BelongsToAssociation> $belongs_to
     * @param array<HasManyAssociation> $has_many
     */
    public function __construct(
        public array $belongs_to,
        public array $has_many,
    ) {
    }
}
