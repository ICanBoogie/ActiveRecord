<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;
use LogicException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Character implements ColumnAttribute
{
    public const MAX_SIZE = 65535;

    /**
     * @param positive-int $size
     *     Maximum number of characters.
     * @param bool $fixed
     *     Whether `$size` is fixed instead of a maximum.
     *     Switch the resulting column from `VARCHAR` to `CHAR`.
     * @param bool $null
     *     Whether values are nullable.
     * @param bool $unique
     *     Whether values are unique.
     * @param non-empty-string|null $collate
     *     A collation identifier.
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/adding-collation.html
     */
    public function __construct(
        public readonly int $size = 255,
        public readonly bool $fixed = false,
        public readonly bool $null = false,
        public readonly bool $unique = false,
        public readonly ?string $collate = null,
    ) {
        if ($fixed && $size > 256) {
            throw new LogicException("The maximum size for fixed character is 255, given: $size");
        }

        $size <= self::MAX_SIZE
            or throw new LogicException("The maximum size for fixed character is 65535, given: $size");
    }
}
