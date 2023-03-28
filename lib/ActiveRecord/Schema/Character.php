<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;
use LogicException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Character extends Constraints implements ColumnAttribute
{
    public const MAX_SIZE = 65535;

    public static function __set_state(array $an_array): object
    {
        return new self(...$an_array);
    }

    /**
     * @param positive-int $size
     *     Maximum number of characters.
     * @param bool $fixed
     *     Whether `$size` is fixed instead of a maximum.
     *     Switch the resulting column from `VARCHAR` to `CHAR`.
     */
    public function __construct(
        public readonly int $size = 255,
        public readonly bool $fixed = false,
        bool $null = false,
        ?string $default = null,
        bool $unique = false,
        ?string $collate = null,
    ) {
        if ($fixed && $size > 256) {
            throw new LogicException("The maximum size for fixed character is 255, given: $size");
        }

        $size <= self::MAX_SIZE
            or throw new LogicException("The maximum size for fixed character is 65535, given: $size");

        parent::__construct(
            null: $null,
            default: $default,
            unique: $unique,
            collate: $collate,
        );
    }
}
