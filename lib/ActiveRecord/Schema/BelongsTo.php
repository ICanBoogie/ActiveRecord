<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;
use ICanBoogie\ActiveRecord;

/**
 * Marks a relationship with another model, with the property as reference.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class BelongsTo extends Integer
{
    /**
     * @param array{
     *     associate: class-string<ActiveRecord>,
     *     size: Integer::SIZE_*,
     *     null: bool,
     *     unique: bool,
     *     as: non-empty-string|null,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(
            $an_array['associate'],
            $an_array['size'],
            $an_array['null'],
            $an_array['unique'],
            $an_array['as'],
        );
    }

    /**
     * @param class-string<ActiveRecord> $associate
     *     The associate ActiveRecord class.
     * @param non-empty-string|null $as
     *     The name of prototype getter for the association, by default it is build according to the column name e.g.
     *    `article_id` would result is a `article` getter.
     */
    public function __construct( // @phpstan-ignore-line
        public string $associate,
        int $size = Integer::SIZE_REGULAR,
        bool $null = false,
        bool $unique = false,
        public ?string $as = null,
    ) {
        parent::__construct(
            size: $size,
            null: $null,
            unique: $unique,
        );
    }
}
