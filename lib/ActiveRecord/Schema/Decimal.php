<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

/**
 * Represents a decimal value:
 *
 * https://dev.mysql.com/doc/refman/8.0/en/fixed-point-types.html
 * https://dev.mysql.com/doc/refman/8.0/en/floating-point-types.html
 * https://www.postgresql.org/docs/current/datatype-numeric.html#DATATYPE-NUMERIC-DECIMAL
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Decimal extends Column
{
    /**
     * @param array{
     *     precision: positive-int,
     *     scale: int,
     *     approximate: bool,
     *     null: bool,
     *     default: ?non-empty-string,
     *     unique: bool,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(
            $an_array['precision'],
            $an_array['scale'],
            $an_array['approximate'],
            $an_array['null'],
            $an_array['default'],
            $an_array['unique'],
        );
    }

    /**
     * @param positive-int $precision
     *     The total count of significant digits in the whole number, that is,
     *     the number of digits to both sides of the decimal point.
     * @param int $scale
     *     The count of decimal digits in the fractional part, to the right of the decimal point.
     *     So the number 23.5141 has a precision of 6 and a scale of 4.
     * @param bool $approximate
     *     Whether stored values can be approximate i.e. inexact.
     *     If `true` resulting column type will be `FLOAT` or `DOUBLE` instead of `NUMERIC`, `DECIMAL`, or `REAL`.
     */
    public function __construct(
        public int $precision,
        public int $scale = 0,
        public bool $approximate = false,
        bool $null = false,
        ?string $default = null,
        bool $unique = false,
    ) {
        parent::__construct(
            null: $null,
            default: $default,
            unique: $unique,
        );
    }
}
