<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;
use LogicException;

/**
 * Represents a string, that can be a binary one.
 *
 * - `VARCHAR` is `Character`
 * - `CHAR` is `Character(fixed: true)`
 * - `VARBINARY` is `Character(binary: true)`
 * - `BINARY` is `Character(fixed:true, binary: true)`
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Character extends Constraints implements SchemaColumn
{
    public const MAX_SIZE = 65535;

    /**
     * @param array{
     *     size: positive-int,
     *     fixed: bool,
     *     binary: bool,
     *     null: bool,
     *     default: ?string,
     *     unique: bool,
     *     collate: non-empty-string|null,
     * } $an_array
     *
     * @return object
     */
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
        public readonly bool $binary = false,
        bool $null = false,
        ?string $default = null,
        bool $unique = false,
        ?string $collate = null,
    ) {
        if ($fixed && $size > 255) {
            throw new LogicException("The maximum size for fixed character is 255, given: $size");
        }

        $size <= self::MAX_SIZE
            or throw new LogicException("The maximum size for fixed character is 65535, given: $size");

        if ($binary && $collate) {
            throw new LogicException("Collate does not apply to binary types");
        }

        parent::__construct(
            null: $null,
            default: $default,
            unique: $unique,
            collate: $collate,
        );
    }
}
