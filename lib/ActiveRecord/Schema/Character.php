<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;
use InvalidArgumentException;

/**
 * Represents a string, that can be a binary one.
 *
 * - `VARCHAR(255)` is `Character`
 * - `CHAR(32)` is `Character(32, fixed: true)`
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Character extends Column
{
    /**
     * @param array{
     *     size: positive-int,
     *     fixed: bool,
     *     null: bool,
     *     default: ?string,
     *     unique: bool,
     *     collate: non-empty-string|null,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param positive-int $size
     *     Maximum number of characters or size.
     * @param bool $fixed
     *     Whether `$size` is fixed instead of a maximum.
     *     A truthful `$fixed` will result in `CHAR` column rather than a `VARCHAR`.
     */
    public function __construct(
        public readonly int $size = 255,
        public readonly bool $fixed = false,
        bool $null = false,
        ?string $default = null,
        bool $unique = false,
        ?string $collate = null,
    ) {
        if ($fixed && $size > 255) {
            throw new InvalidArgumentException("For fixed character, the size must be less than 255, given: $size");
        }

        parent::__construct(
            null: $null,
            default: $default,
            unique: $unique,
            collate: $collate,
        );
    }
}
