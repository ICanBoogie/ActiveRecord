<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;
use InvalidArgumentException;
use LogicException;

/**
 * Represents a string, that can be a binary one.
 *
 * - `VARBINARY(255)` is `Binary`
 * - `BINARY(32)` is `Binary(32, fixed: true)`
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Binary extends Column
{
    /**
     * @param array{
     *     size: positive-int,
     *     fixed: bool,
     *     null: bool,
     *     default: ?string,
     *     unique: bool,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(
            $an_array['size'],
            $an_array['fixed'],
            $an_array['null'],
            $an_array['default'],
            $an_array['unique'],
        );
    }

    /**
     * @param positive-int $size
     *     Maximum number of characters or size.
     * @param bool $fixed
     *     Whether `$size` is fixed instead of a maximum.
     *     A truthful `$fixed` will result in `BINARY` column rather than a `VARBINARY`.
     */
    public function __construct(
        public int $size = 255,
        public bool $fixed = false,
        bool $null = false,
        ?string $default = null,
        bool $unique = false,
    ) {
        if ($fixed && $size > 255) {
            throw new InvalidArgumentException("For fixed binary, the size must be less than 255, given: $size");
        }

        parent::__construct(
            null: $null,
            default: $default,
            unique: $unique,
        );
    }
}
