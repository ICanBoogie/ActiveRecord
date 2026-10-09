<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;
use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\ConfigBuilder;

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
     *     unsigned: bool,
     *     null: bool,
     *     unique: bool,
     *     as: non-empty-string|null,
     *     on_delete: OnDelete|null,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(
            $an_array['associate'],
            $an_array['size'],
            $an_array['unsigned'],
            $an_array['null'],
            $an_array['unique'],
            $an_array['as'],
            $an_array['on_delete'],
        );
    }

    /**
     * @param class-string<ActiveRecord> $associate
     *     The associate ActiveRecord class.
     * @param bool $unsigned
     *     Whether values are unsigned. You don't need to set this one, the {@see ConfigBuilder}
     *     aligns it on the referenced primary key when a foreign key is created.
     * @param non-empty-string|null $as
     *     The name of prototype getter for the association, by default, it is built according to the column name e.g.
     *    `article_id` would result in an `article` getter.
     * @param OnDelete|null $on_delete
     *     If defined, a foreign key constraint is created with the table, with that action on delete.
     *     The size and signedness of the column are then aligned on the referenced primary key.
     */
    public function __construct(
        public string $associate,
        int $size = Integer::SIZE_REGULAR,
        bool $unsigned = false,
        bool $null = false,
        bool $unique = false,
        public ?string $as = null,
        public ?OnDelete $on_delete = null,
    ) {
        parent::__construct(
            size: $size,
            unsigned: $unsigned,
            null: $null,
            unique: $unique,
        );
    }
}
