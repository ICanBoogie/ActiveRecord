<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;
use LogicException;

use function in_array;

/**
 * Represents an integer value:
 *
 * - `TINYINT` is `Integer(size: Integer::SIZE_TINY)` or `Integer(1)`
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Integer implements ColumnAttribute
{
    public const SIZE_TINY = 1;
    public const SIZE_SMALL = 2;
    public const SIZE_MEDIUM = 3;
    public const SIZE_REGULAR = 4;
    public const SIZE_BIG = 8;

    private const ALLOWED_SIZES = [
        self::SIZE_TINY,
        self::SIZE_SMALL,
        self::SIZE_MEDIUM,
        self::SIZE_REGULAR,
        self::SIZE_BIG
    ];

    /**
     * @param positive-int $size
     *     Number of bytes used to store values. Must be one of: {@link self::SIZE_TINY}, {@link self::SIZE_SMALL},
     *     {@link self::SIZE_MEDIUM}, {@link self::SIZE_REGULAR}, {@link self::SIZE_BIG}.
     * @param bool $unsigned
     *     Whether values are unsigned.
     *     Values are signed by default.
     * @param bool $null
     *     Whether values can be nullable.
     *     Values are not nullable by default.
     * @param bool $serial
     *     An integer that is automatically incremented by the database. This has a few constraints:
     *     - `$size` must at least 2 bytes
     *     - `$unsigned` must be `true`
     *     - `$null` must be `false`
     *     - `$unique` must be `true`
     *     Values are not serial by default.
     * @param bool $unique
     *     Whether values must be unique.
     *     Values are not unique by default.
     */
    public function __construct(
        public readonly int $size = self::SIZE_REGULAR,
        public readonly bool $unsigned = false,
        public readonly bool $null = false,
        public readonly bool $serial = false,
        public readonly bool $unique = false,
    ) {
        in_array($size, self::ALLOWED_SIZES)
            or throw new LogicException("Size must be one of the allowed ones");

        if ($serial) {
            $size > 1 or throw new LogicException("A serial integer must be at least 2 bytes");
            $unsigned or throw new LogicException("A serial integer must be unsigned");
            !$null or throw new LogicException("A serial integer cannot be nullable");
            $unique or throw new LogicException("A serial integer must be unique");
        }
    }
}
